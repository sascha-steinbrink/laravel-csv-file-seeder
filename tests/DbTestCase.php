<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\tests;


use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * DbTestCase
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 18.05.2019
 * @version : 1.0
 * @package SaschaSteinbrink\LaravelCsvFileSeeder
 */
abstract class DbTestCase extends FileTestCase
{
    use DatabaseTransactions;

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite test database
        $app['config']->set('database.default', 'sqlite_testing');
        $app['config']->set('database.connections.sqlite_testing', [
            'driver'                  => 'sqlite',
            'database'                => __DIR__ . '/Files/test.sqlite',
            'prefix'                  => ''
        ]);

        $app['config']->set('laravel-csv-file-seeder', [
            'connection' => 'sqlite_testing',
            'data_path'  => __DIR__ .  '/tmp',
            'archive_name' => 'db-csv-export.zip',
            'commands'   => [
                'export_csv' => [
                    'except' => ['sqlite_sequence'],
                ],
            ],
        ]);

        $app['config']->set('test.file-path', __DIR__ . '/Files');
        $app['config']->set('test.tmp-path', __DIR__ . '/tmp');
    }
}