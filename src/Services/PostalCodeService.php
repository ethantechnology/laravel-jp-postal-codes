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
    public const ZIP_FILE_NAME = 'ken_all.zip';

    /**
     * CSV file name inside the ZIP.
     */
    public const CSV_FILE_NAME = 'KEN_ALL.CSV';

    /**
     * Character encodings to try when converting CSV data.
     */
    public const CHARACTER_CODE_FROM = ['SJIS-win', 'CP932', 'UTF-8'];

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
        $this->downloadUrl = config('jp-postal-codes.postal_code_url', 'https://www.post.japanpost.jp/zipcode/dl/kogaki/zip/ken_all.zip');
        
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
            throw new DownloadErrorException('Failed to open ZIP file: ' . $this->zipFilePath);
        }
        
        // Japan Post uses KEN_ALL.CSV
        $csvIndex = $this->zip->locateName(self::CSV_FILE_NAME);
        
        if ($csvIndex === false) {
            throw new FormatErrorException('CSV file not found in ZIP: ' . self::CSV_FILE_NAME);
        }
        
        $csvContent = $this->zip->getFromIndex($csvIndex);
        
        // Convert encoding from Shift-JIS to UTF-8
        $encodingDetected = false;
        foreach (self::CHARACTER_CODE_FROM as $encoding) {
            $utf8Content = mb_convert_encoding($csvContent, self::CHARACTER_CODE_TO, $encoding);
            if ($utf8Content !== false) {
                $encodingDetected = true;
                break;
            }
        }
        
        if (!$encodingDetected) {
            throw new FormatErrorException('Failed to convert CSV encoding');
        }
        
        // Create a temporary file with UTF-8 content
        $tempFile = tempnam(sys_get_temp_dir(), 'postal_code_');
        file_put_contents($tempFile, $utf8Content);
        
        // Open the file for reading
        $this->fp = fopen($tempFile, 'r');
        
        if ($this->fp === false) {
            throw new FormatErrorException('Failed to open CSV file');
        }
    }
    
    /**
     * Convert CSV data to array of objects.
     *
     * @return array
     * @throws FormatErrorException
     */
    private function csvToArray()
    {
        if ($this->fp === null || feof($this->fp)) {
            $this->isLast = true;
            return [];
        }
        
        $result = [];
        $i = 0;
        
        while ($i < self::CHUNK && ($line = fgetcsv($this->fp)) !== false) {
            if (count($line) < 1) {
                continue;
            }
            
            $obj = $this->processLine($line);
            if ($obj) {
                $result[] = $obj;
                $i++;
            }
            
            $this->count++;
        }
        
        if (feof($this->fp)) {
            $this->isLast = true;
            $this->closeResources();
        }
        
        return $result;
    }
    
    /**
     * Process a CSV line into an object.
     *
     * @param array $line
     * @return stdClass|null
     */
    private function processLine(array $line)
    {
        // Japan Post CSV format has 15 columns
        if (count($line) < 15) {
            return null;
        }
        
        $obj = new stdClass();
        
        // Map data from Japan Post CSV format to our model
        $obj->addressCode = $line[0]; // 全国地方公共団体コード
        $obj->prefectureCode = (int)substr($line[0], 0, 2); // 都道府県コード (最初の2桁)
        $obj->cityCode = (int)substr($line[0], 0, 5); // 市区町村コード (最初の5桁)
        $obj->areaCode = (int)$line[0]; // 町域コード (全体)
        $obj->postalCode = $line[2]; // 郵便番号
        $obj->prefecture = $line[6]; // 都道府県名
        $obj->prefectureKana = $line[3]; // 都道府県名カナ
        $obj->city = $line[7]; // 市区町村名
        $obj->cityKana = $line[4]; // 市区町村名カナ
        $obj->area = $line[8]; // 町域名
        $obj->areaKana = $line[5]; // 町域名カナ
        
        // フラグ情報
        $obj->isOffice = false; // 事業所フラグ (KEN_ALL.CSVには含まれていない)
        $obj->isClosed = false; // 廃止フラグ (KEN_ALL.CSVには含まれていない)
        
        // その他の情報
        $obj->areaInfo = '';
        $obj->kyotoRoadName = '';
        $obj->chome = '';
        $obj->chomeKana = '';
        $obj->info = '';
        $obj->officeName = '';
        $obj->officeNameKana = '';
        $obj->officeAddress = '';
        $obj->newAddressCode = '';
        
        return $obj;
    }
    
    /**
     * Close file resources.
     */
    private function closeResources()
    {
        if ($this->fp) {
            fclose($this->fp);
            $this->fp = null;
        }
        
        if ($this->zip) {
            $this->zip->close();
            $this->zip = null;
        }
    }
    
    /**
     * Destructor to ensure resources are closed.
     */
    public function __destruct()
    {
        $this->closeResources();
    }
}
