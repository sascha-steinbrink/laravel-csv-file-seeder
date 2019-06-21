<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Tests\Unit\Compression;


use SaschaSteinbrink\LaravelCsvFileSeeder\Tests\FileTestCase;
use SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Compressor;
use SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Decompressor;

/**
 * CompressionTestCase
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 13.06.2019
 * @version : 1.0
 * @package SaschaSteinbrink\LaravelCsvFileSeeder\Tests\Unit\Compression
 */
abstract class CompressionTestCase extends FileTestCase
{
    /**
     * Get a compressor.
     *
     * @return Compressor
     */
    protected function makeZip()
    {
        return Compressor::create()
                         ->make(
                             "$this->tmpPath/export.zip",
                             "$this->filePath/*.csv"
                         );
    }

    /**
     * Get a decompressor.
     *
     * @param string $archiveName
     *
     * @return Decompressor
     */
    protected function makeUnzip(string $archiveName)
    {
        return Decompressor::create()
                           ->make(
                               "../Files/$archiveName",
                               '.',
                               $this->tmpPath
                           );
    }
}