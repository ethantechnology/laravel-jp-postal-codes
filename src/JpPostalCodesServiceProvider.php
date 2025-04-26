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
        if ($this->app->runningInConsole()) {
            // Register commands
            $this->commands([
                UpdatePostalCodesCommand::class,
            ]);

            $publishesMigrationsMethod = method_exists($this, 'publishesMigrations')
                ? 'publishesMigrations'
                : 'publishes';
            
            // Publish migrations
            $this->{$publishesMigrationsMethod}([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'jp-postal-codes-migrations');
            
            // Publish config file
            $this->publishes([
                __DIR__.'/../config/jp-postal-codes.php' => config_path('jp-postal-codes.php'),
            ], 'jp-postal-codes-config');
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