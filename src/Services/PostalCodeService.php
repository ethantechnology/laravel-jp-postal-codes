<?php

namespace Eta\JpPostalCodes\Services;

use ZipArchive;
use Eta\JpPostalCodes\Exceptions\DownloadErrorException;
use Eta\JpPostalCodes\Exceptions\FormatErrorException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use stdClass;

class PostalCodeService
{
    /**
     * ZIP file name.
     */
    public const ZIP_FILE_NAME = 'csv_zenkoku.zip';

    /**
     * CSV file name inside the ZIP.
     */
    public const CSV_FILE_NAME = 'zenkoku.csv';

    /**
     * Character encodings to try when converting CSV data.
     */
    public const CHARACTER_CODE_FROM = ['SJIS-win', 'UTF-8', 'CP932'];

    /**
     * Target character encoding for CSV data.
     */
    public const CHARACTER_CODE_TO = 'UTF-8';

    /**
     * Number of records to process in each batch.
     */
    public const CHUNK = 250;

    /**
     * URL to download postal code data.
     *
     * @var string
     */
    private $downloadUrl;

    /**
     * Path to the downloaded ZIP file.
     * 
     * @var string
     */
    private $zipFilePath;

    /**
     * ZIP archive handler.
     * 
     * @var ZipArchive|null
     */
    private $zip = null;

    /**
     * CSV file pointer.
     * 
     * @var resource|null
     */
    private $fp;

    /**
     * Current line count being processed.
     * 
     * @var int
     */
    private $count = 0;

    /**
     * Flag indicating if all data has been read.
     * 
     * @var bool
     */
    private $isLast = false;

    /**
     * HTTP client for downloading data.
     * 
     * @var Client
     */
    private $httpClient;

    /**
     * PostalCodeService constructor.
     * 
     * @param \GuzzleHttp\Client|null $httpClient
     */
    public function __construct(Client $httpClient = null)
    {
        $this->httpClient = $httpClient ?? new Client();
        $this->downloadUrl = config('jp-postal-codes.postal_code_url', 'http://jusyo.jp/downloads/new/csv/csv_zenkoku.zip');
        
        $zipFileDir = storage_path('app/jp-postal-codes/');
        
        if (!file_exists($zipFileDir)) {
            mkdir($zipFileDir, 0755, true);
        }
        
        $this->zipFilePath = $zipFileDir . self::ZIP_FILE_NAME;
    }

    /**
     * Get the download URL for postal code data.
     *
     * @return string
     */
    public function getDownloadUrl()
    {
        return $this->downloadUrl;
    }

    /**
     * Get the next batch of postal code data.
     *
     * @return array
     * @throws DownloadErrorException
     * @throws FormatErrorException
     */
    public function getData()
    {
        if ($this->zip === null) {
            $this->prepareData();
        }
        
        return $this->csvToArray();
    }

    /**
     * Check if more data is available.
     *
     * @return bool
     */
    public function hasNext()
    {
        return !$this->isLast;
    }

    /**
     * Download the ZIP file if not exists and prepare the archive.
     *
     * @return void
     * @throws DownloadErrorException
     * @throws FormatErrorException
     */
    private function prepareData()
    {
        $this->downloadZipIfNeeded();
        $this->openArchive();
    }

    /**
     * Download the ZIP file if it doesn't exist locally.
     *
     * @return void
     * @throws DownloadErrorException
     */
    private function downloadZipIfNeeded()
    {
        if (file_exists($this->zipFilePath)) {
            return;
        }
        
        try {
            $response = $this->httpClient->request('GET', $this->downloadUrl);
            
            if ($response->getStatusCode() !== 200) {
                throw new DownloadErrorException(
                    'Failed to download postal code ZIP file from URL: ' . $this->downloadUrl . 
                    '. Status code: ' . $response->getStatusCode()
                );
            }
            
            file_put_contents($this->zipFilePath, $response->getBody()->getContents());
            
        } catch (GuzzleException $e) {
            Log::error('Failed to download postal code data from URL: ' . $this->downloadUrl . '. Error: ' . $e->getMessage());
            throw new DownloadErrorException(
                'Failed to download postal code data from URL: ' . $this->downloadUrl . '. Error: ' . $e->getMessage(), 
                0, 
                $e
            );
        }
    }

    /**
     * Open the ZIP archive and prepare CSV stream.
     *
     * @return void
     * @throws DownloadErrorException
     * @throws FormatErrorException
     */
    private function openArchive()
    {
        $this->zip = new ZipArchive();
        
        if ($this->zip->open($this->zipFilePath) !== true) {
            throw new DownloadErrorException('Failed to open ZIP file: ' . $this->zipFilePath . ' (downloaded from ' . $this->downloadUrl . ')');
        }
        
        $this->fp = $this->zip->getStream(self::CSV_FILE_NAME);
        
        if (!$this->fp) {
            throw new FormatErrorException('Failed to open CSV file "' . self::CSV_FILE_NAME . '" in ZIP archive from ' . $this->downloadUrl);
        }
    }

    /**
     * Parse CSV data to an array of objects.
     *
     * @return array
     * @throws FormatErrorException
     */
    private function csvToArray()
    {
        $resultArr = [];
        
        for ($i = 0; $i < self::CHUNK; $i++) {
            // Get a line from the CSV file
            $line = fgets($this->fp);
            
            // Check if end of file or invalid data
            if ($line === false) {
                if ($this->count > 0) {
                    break;
                }
                throw new FormatErrorException('Invalid CSV format in file from ' . $this->downloadUrl);
            }
            
            // Convert encoding
            $line = mb_convert_encoding($line, self::CHARACTER_CODE_TO, self::CHARACTER_CODE_FROM);
            $line = str_replace('"', '', $line);
            
            // Skip header row
            if ($this->count !== 0 && !empty($line)) {
                $this->processLine($line, $resultArr);
            }
            
            $this->count++;
        }
        
        // Check if we've reached the end of the file
        if (feof($this->fp)) {
            $this->closeResources();
        }
        
        return $resultArr;
    }

    /**
     * Process a single CSV line.
     *
     * @param string $line
     * @param array $resultArr
     * @return void
     */
    private function processLine($line, array &$resultArr)
    {
        $data = explode(',', $line);
        
        // Ensure we have at least the minimum required fields
        if (count($data) < 8) {
            return;
        }
        
        $obj = new stdClass();
        
        // Map CSV fields to object properties
        $obj->addressCode = $data[0] ?? null;
        $obj->prefectureCode = $data[1] ?? null;
        $obj->cityCode = $data[2] ?? null;
        $obj->areaCode = $data[3] ?? null;
        $obj->postalCode = $data[4] ?? null;
        $obj->isOffice = isset($data[5]) ? (int)$data[5] === 1 : false;
        $obj->isClosed = isset($data[6]) ? (int)$data[6] === 1 : false;
        $obj->prefecture = $data[7] ?? null;
        $obj->prefectureKana = $data[8] ?? null;
        $obj->city = $data[9] ?? null;
        $obj->cityKana = $data[10] ?? null;
        $obj->area = $data[11] ?? null;
        $obj->areaKana = $data[12] ?? null;
        $obj->areaInfo = $data[13] ?? null;
        $obj->kyotoRoadName = $data[14] ?? null;
        $obj->chome = $data[15] ?? null;
        $obj->chomeKana = $data[16] ?? null;
        $obj->info = $data[17] ?? null;
        $obj->officeName = $data[18] ?? null;
        $obj->officeNameKana = $data[19] ?? null;
        $obj->officeAddress = $data[20] ?? null;
        $obj->newAddressCode = $data[21] ?? null;
        
        $resultArr[] = $obj;
    }

    /**
     * Close file resources.
     *
     * @return void
     */
    private function closeResources()
    {
        fclose($this->fp);
        $this->zip->close();
        $this->isLast = true;
    }
    
    /**
     * Destructor: Ensure resources are closed.
     */
    public function __destruct()
    {
        if ($this->fp && is_resource($this->fp)) {
            fclose($this->fp);
        }
        
        if ($this->zip instanceof ZipArchive) {
            $this->zip->close();
        }
    }
}
