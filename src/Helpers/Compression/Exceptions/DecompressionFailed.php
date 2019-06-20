<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Exceptions;


use Exception;

/**
 * DecompressionFailed
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 15.05.2019
 * @version : 1.0
 * @package SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Exceptions
 */
class DecompressionFailed extends Exception
{
    /**
     * @return DecompressionFailed
     */
    public static function noFilesWereExtracted()
    {
        return new static('No files were extracted!');
    }

    /**
     * @return DecompressionFailed
     */
    public static function encryptionFlagMustBeSet() {
        return new static("The archive is encrypted therefore the 'encrypted' option must be enabled to decompress the archive.");
    }

    /**
     * @return DecompressionFailed
     */
    public static function archiveNotFound()
    {
        return new static("The archive could not be found!");
    }
}