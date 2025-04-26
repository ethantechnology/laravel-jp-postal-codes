<?php

namespace Eta\JapanRegions;

use Eta\JapanRegions\Commands\ImportJapanRegionsCommand;
use Illuminate\Support\ServiceProvider;

class JapanRegionsServiceProvider extends ServiceProvider
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
                ImportJapanRegionsCommand::class,
            ]);
            
            // Publish config file
            $this->publishes([
                __DIR__.'/../config/japan-regions.php' => config_path('japan-regions.php'),
            ], 'japan-regions-config');
            
            // Publish data
            $this->publishes([
                __DIR__.'/../database/data' => database_path('data/japan-regions'),
            ], 'japan-regions-data');
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
        $this->mergeConfigFrom(__DIR__.'/../config/japan-regions.php', 'japan-regions');
    }
} 