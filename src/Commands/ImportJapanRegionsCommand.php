<?php

namespace Eta\JapanRegions\Commands;

use Eta\JapanRegions\Models\Prefecture;
use Eta\JapanRegions\Models\PostalCode;
use Eta\JapanRegions\Services\PostalCodeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportJapanRegionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'japan-regions:import {--only-prefectures : Import only prefectures data} {--only-postal-codes : Import only postal codes data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Japanese prefectures and postal codes data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Importing Japanese prefectures and postal codes...');

        $onlyPrefectures = $this->option('only-prefectures');
        $onlyPostalCodes = $this->option('only-postal-codes');

        try {
            // Import prefectures if needed
            if (!$onlyPostalCodes) {
                $this->importPrefectures();
            }

            // Import postal codes if needed
            if (!$onlyPrefectures) {
                $this->importPostalCodes();
            }

            $this->info('Import completed successfully!');
            
            // Display publish instructions if config file doesn't exist
            if (!file_exists(config_path('japan-regions.php'))) {
                $this->comment('');
                $this->comment('To customize table names, publish the config file:');
                $this->comment('php artisan vendor:publish --tag=japan-regions-config');
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error during import: ' . $e->getMessage());
            
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
     * Import prefectures from local data file.
     *
     * @return void
     */
    private function importPrefectures()
    {
        $this->info('Importing prefectures...');
        
        try {
            $prefectures = include __DIR__ . '/../../database/data/prefectures.php';
            
            // Use transaction for better data consistency
            DB::beginTransaction();
            
            Prefecture::upsert($prefectures, ['id'], ['name']);
            
            DB::commit();
            
            $this->info('Prefectures imported successfully: ' . count($prefectures) . ' records');
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \RuntimeException('Failed to import prefectures: ' . $e->getMessage(), 0, $e);
        }
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
            $bar = $this->output->createProgressBar();
            $bar->start();
            
            $totalInserted = 0;
            $chunkSize = config('japan-regions.import.chunk_size', 1000);
            
            DB::beginTransaction();
            
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
                    
                    // Import in chunks to manage memory
                    if (count($data) >= $chunkSize) {
                        $this->importPostalCodeChunk($data);
                        $bar->advance(count($data));
                        $data = [];
                    }
                }
                
                // Import any remaining records
                if (!empty($data)) {
                    $this->importPostalCodeChunk($data);
                    $bar->advance(count($data));
                }
            }
            
            DB::commit();
            
            $bar->finish();
            $this->newLine();
            $this->info("Imported {$totalInserted} postal codes successfully.");
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \RuntimeException('Failed to import postal codes: ' . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Import a chunk of postal code data.
     *
     * @param array $data
     * @return void
     */
    private function importPostalCodeChunk(array $data)
    {
        if (empty($data)) {
            return;
        }
        
        PostalCode::upsert($data, ['address_code'], PostalCode::COLUMNS);
    }
} 