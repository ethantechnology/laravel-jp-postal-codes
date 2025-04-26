<?php

namespace Eta\JpPostalCodes\Commands;

use Eta\JpPostalCodes\Models\City;
use Eta\JpPostalCodes\Models\Prefecture;
use Eta\JpPostalCodes\Models\PostalCode;
use Eta\JpPostalCodes\Services\PostalCodeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UpdatePostalCodesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jp-postal-codes:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Japanese prefectures, cities and postal codes data from Japan Post';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Updating Japanese prefectures, cities and postal codes...');
        $this->info('Data will be downloaded from official Japan Post service.');

        try {
            // Check if required tables exist
            if (!$this->tablesExist()) {
                $this->error('Required tables do not exist. Please publish and run migrations first:');
                $this->line('php artisan vendor:publish --tag=jp-postal-codes-migrations');
                $this->line('php artisan migrate');
                return Command::FAILURE;
            }

            // 1. Truncate tables if they exist to ensure clean data
            $this->truncateTables();
            
            // 2. Import postal codes 
            $this->importPostalCodes();
            
            // 3. Generate prefectures and cities from postal code data
            $this->generatePrefecturesFromPostalCodes();
            $this->generateCitiesFromPostalCodes();
            
            // 4. Clean up downloaded files
            $this->cleanupDownloadedFiles();

            $this->info('Update completed successfully!');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            // Clean up downloaded files even on error
            $this->cleanupDownloadedFiles();
            
            $this->error('Error during update: ' . $e->getMessage());
            
            // Show detailed error information in verbose mode
            if ($this->getOutput()->isVerbose()) {
                $this->error('Exception details: ' . get_class($e));
                $this->error('Stack trace:');
                $this->error($e->getTraceAsString());
            } else {
                $this->info('Run with -v for more detailed error information.');
            }
            
            return Command::FAILURE;
        }
    }

    /**
     * Check if required tables exist
     *
     * @return bool
     */
    private function tablesExist()
    {
        // Get table names from config or use defaults
        $postalCodesTable = config('jp-postal-codes.tables.postal_codes', 'jp_postal_codes');
        $prefecturesTable = config('jp-postal-codes.tables.prefectures', 'jp_prefectures');
        $citiesTable = config('jp-postal-codes.tables.cities', 'jp_cities');
        
        // Check if tables exist using Schema facade
        return Schema::hasTable($postalCodesTable) &&
            Schema::hasTable($prefecturesTable) &&
            Schema::hasTable($citiesTable);
    }

    /**
     * Truncate tables to ensure clean data.
     *
     * @return void
     */
    private function truncateTables()
    {
        $this->info('Preparing tables for fresh data...');
        
        Schema::disableForeignKeyConstraints();
        
        PostalCode::truncate();
        Prefecture::truncate();
        City::truncate();
        
        Schema::enableForeignKeyConstraints();
        
        $this->info('Tables prepared successfully.');
    }

    /**
     * Import postal codes from downloaded CSV data.
     *
     * @return void
     */
    private function importPostalCodes()
    {
        $postalCodeService = new PostalCodeService();
        
        $this->info('Importing postal codes...');
        $this->info('This may take a while as data will be downloaded and processed from: ' . $postalCodeService->getDownloadUrl());
        
        try {
            // Estimate total records (approx 124,000 for Japan Post dataset)
            $estimatedTotal = 125000;
            $bar = $this->output->createProgressBar($estimatedTotal);
            $bar->start();
            
            $totalInserted = 0;
            $chunkSize = config('jp-postal-codes.import.chunk_size', 1000);
            
            while ($postalCodeService->hasNext()) {
                $postalCodes = $postalCodeService->getData();
                
                if (empty($postalCodes)) {
                    continue;
                }
                
                $data = [];
                
                foreach ($postalCodes as $index => $postalCode) {
                    $row = [];
                    
                    // Map data from camelCase object properties to snake_case database columns
                    foreach (PostalCode::COLUMNS as $column) {
                        $camelColumn = Str::camel($column);
                        $row[$column] = property_exists($postalCode, $camelColumn) ? $postalCode->$camelColumn : null;
                    }
                    
                    $data[] = $row;
                    $totalInserted++;
                    
                    // Import postal codes in chunks to manage memory
                    if (count($data) >= $chunkSize) {
                        PostalCode::insert($data);
                        $bar->advance(count($data));
                        $data = [];
                    }
                }
                
                // Import any remaining postal code records
                if (!empty($data)) {
                    PostalCode::insert($data);
                    $bar->advance(count($data));
                }
            }
            
            $bar->finish();
            $this->newLine();
            $this->info("Imported {$totalInserted} postal codes successfully.");
            
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to import postal codes: ' . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Generate prefectures from postal code data.
     *
     * @return void
     */
    private function generatePrefecturesFromPostalCodes()
    {
        $this->info('Generating prefectures from postal code data...');
        
        // Use raw SQL to efficiently extract and group prefecture data
        $prefectures = PostalCode::query()
            ->selectRaw('prefecture_code as id, prefecture as name')
            ->whereNotNull('prefecture_code')
            ->whereNotNull('prefecture')
            ->groupBy('prefecture_code', 'prefecture')
            ->get();
            
        if ($prefectures->isEmpty()) {
            $this->warn('No prefecture data found in postal codes!');
            return;
        }
        
        // Prepare data for bulk insert
        $prefectureData = [];
        foreach ($prefectures as $prefecture) {
            $prefectureData[] = [
                'id' => (int)$prefecture->id,
                'name' => $prefecture->name,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        
        // Create progress bar for prefectures (though it's a small dataset)
        $bar = $this->output->createProgressBar(1);
        $bar->start();
        
        // Bulk insert all prefectures
        Prefecture::insert($prefectureData);
        $bar->advance();
        
        $bar->finish();
        $this->newLine();
        $this->info('Successfully generated ' . count($prefectureData) . ' prefectures from postal code data.');
    }
    
    /**
     * Generate cities from postal code data.
     *
     * @return void
     */
    private function generateCitiesFromPostalCodes()
    {
        $this->info('Generating cities from postal code data...');
        
        // Use raw SQL to efficiently extract and group city data
        $cities = PostalCode::query()
            ->selectRaw('address_code as id, prefecture_code as prefecture_id, city as name')
            ->whereNotNull('address_code')
            ->whereNotNull('prefecture_code')
            ->whereNotNull('city')
            ->groupBy('address_code', 'prefecture_code', 'city')
            ->get();
            
        if ($cities->isEmpty()) {
            $this->warn('No city data found in postal codes!');
            return;
        }
        
        // Prepare data for bulk insert
        $cityData = [];
        foreach ($cities as $city) {
            $cityData[] = [
                'id' => $city->id,
                'prefecture_id' => (int)$city->prefecture_id,
                'name' => $city->name,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        
        // Create progress bar for cities
        $totalChunks = ceil(count($cityData) / 1000);
        $bar = $this->output->createProgressBar($totalChunks);
        $bar->start();
        
        // Bulk insert all cities in chunks to avoid memory issues
        $chunks = array_chunk($cityData, 1000);
        foreach ($chunks as $chunk) {
            City::insert($chunk);
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info('Successfully generated ' . count($cityData) . ' cities from postal code data.');
    }

    /**
     * Clean up downloaded files after processing
     *
     * @return void
     */
    private function cleanupDownloadedFiles()
    {
        $zipFilePath = storage_path('app/jp-postal-codes/' . PostalCodeService::ZIP_FILE_NAME);
        
        if (file_exists($zipFilePath)) {
            // Attempt to delete the zip file
            try {
                unlink($zipFilePath);
                if ($this->getOutput()->isVerbose()) {
                    $this->info('Successfully removed downloaded file: ' . $zipFilePath);
                }
            } catch (\Exception $e) {
                $this->warn('Failed to remove downloaded file: ' . $zipFilePath);
                if ($this->getOutput()->isVerbose()) {
                    $this->warn('Error: ' . $e->getMessage());
                }
            }
        }
        
        // Also clean temporary files that might have been created
        $tempFiles = glob(sys_get_temp_dir() . '/postal_code_*');
        foreach ($tempFiles as $tempFile) {
            if (is_file($tempFile) && is_writable($tempFile)) {
                @unlink($tempFile);
            }
        }
    }
}