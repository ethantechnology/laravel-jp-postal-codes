<?php

namespace Eta\JpPostalCodes;

use Eta\JpPostalCodes\Commands\ImportPostalCodesCommand;
use Illuminate\Support\ServiceProvider;

class JpPostalCodesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish migrations - they'll be auto-published on install
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'migrations');
        
        if ($this->app->runningInConsole()) {
            // Register commands
            $this->commands([
                ImportPostalCodesCommand::class,
            ]);
            
            // Publish config file
            $this->publishes([
                __DIR__.'/../config/jp-postal-codes.php' => config_path('jp-postal-codes.php'),
            ], 'jp-postal-codes-config');
            
            // Publish data
            $this->publishes([
                __DIR__.'/../database/data' => database_path('data/jp-postal-codes'),
            ], 'jp-postal-codes-data');
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(__DIR__.'/../config/jp-postal-codes.php', 'jp-postal-codes');
    }
} 