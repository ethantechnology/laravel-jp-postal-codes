<?php

namespace Eta\JpPostalCodes;

use Eta\JpPostalCodes\Commands\UpdatePostalCodesCommand;
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
        // Load migrations directly instead of publishing
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        
        // Keep the publish option for those who want to customize
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'jp-postal-codes-migrations');
        
        if ($this->app->runningInConsole()) {
            // Register commands
            $this->commands([
                UpdatePostalCodesCommand::class,
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