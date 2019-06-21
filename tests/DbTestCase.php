<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Tests;


use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * DbTestCase
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 18.05.2019
 * @version : 1.0
 * @package SaschaSteinbrink\LaravelCsvFileSeeder\Tests
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
        parent::getEnvironmentSetUp($app);

        // Setup default database to use sqlite test database
        $app['config']->set('database.default', 'sqlite_testing');
        $app['config']->set('database.connections.sqlite_testing', [
            'driver'                  => 'sqlite',
            'database'                => __DIR__ . '/Files/test.sqlite',
            'prefix'                  => ''
        ]);

        $app['config']->set('test.file-path', __DIR__ . '/Files');
        $app['config']->set('test.tmp-path', __DIR__ . '/tmp');
    }
}