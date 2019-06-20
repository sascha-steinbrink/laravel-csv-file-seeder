<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder;

use Illuminate\Support\ServiceProvider;

/**
 * LaravelCsvFileSeederServiceProvider
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 11.05.2019
 * @version : 1.0
 * @package SaschaSteinbrink\LaravelCsvFileSeeder
 */
class LaravelCsvFileSeederServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laravel-csv-file-seeder.php', 'laravel-csv-file-seeder');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            LaravelCsvFileSeeder::class,
        ];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__ . '/../config/laravel-csv-file-seeder.php' => config_path('laravel-csv-file-seeder.php'),
        ], 'config');

        // Registering package commands.
        $this->commands([
            Commands\CsvSeedCommand::class,
            Commands\CsvExportCommand::class,
        ]);
    }
}
