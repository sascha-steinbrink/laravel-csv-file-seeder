<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\tests;


use Orchestra\Testbench\TestCase;

/**
 * FileTestCase
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 17.05.2019
 * @version : 1.0
 * @package SaschaSteinbrink\LaravelCsvFileSeeder
 */
abstract class FileTestCase extends TestCase
{
    protected $files;
    protected $filePath;
    protected $tmpPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filePath = $this->baseTestPath('Files');
        $this->tmpPath = $this->baseTestPath('tmp');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->clearDirectory($this->tmpPath);
    }

    /**
     * Get the test base path.
     *
     * @param string $path
     *
     * @return string
     */
    protected function baseTestPath(string $path)
    {
        return __DIR__ . "/$path";
    }

    /**
     * Clear the given directory.
     *
     * @param string $dir
     * @param bool   $deleteSelf
     *
     * @return bool
     */
    protected function clearDirectory(string $dir, bool $deleteSelf = false)
    {
        if(!file_exists($dir)) {
            return true;
        }

        if(!is_dir($dir)) {
            return unlink($dir);
        }

        foreach(scandir($dir) AS $file) {
            if (in_array($file, ['.', '..', '.gitignore'])) {
                continue;
            }

            if(!$this->clearDirectory(join(DIRECTORY_SEPARATOR, [$dir, $file]), true)) {
                return false;
            }
        }

        if(!$deleteSelf) {
            return true;
        }

        return rmdir($dir);
    }

    /**
     * Tell Testbench to use this package.
     * @param $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return ['SaschaSteinbrink\LaravelCsvFileSeeder\LaravelCsvFileSeederServiceProvider'];
    }
}