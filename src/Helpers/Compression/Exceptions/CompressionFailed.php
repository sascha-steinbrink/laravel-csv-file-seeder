<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Exceptions;


use Exception;

/**
 * CompressionFailed
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 15.05.2019
 * @version : 1.0
 * @package SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Exceptions
 */
class CompressionFailed extends Exception
{
    /**
     * @return CompressionFailed
     */
    public static function zipFileNotCreated()
    {
        return new static('The zip file could not be created!');
    }

    /**
     * @return CompressionFailed
     */
    public static function zipFileEmpty()
    {
        return new static('The zip file is empty!');
    }
}