<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Exceptions;

use Exception;

/**
 * CompressorStartFailed.
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 15.05.2019
 * @version : 1.0
 */
class CompressorStartFailed extends Exception
{
    /**
     * @param string $parameter
     * @param string $conflictingParameter
     *
     * @return CompressorStartFailed
     */
    public static function conflictingParameters(string $parameter, string $conflictingParameter)
    {
        return new static("Cannot set `$parameter` because it conflicts with parameter `$conflictingParameter`!");
    }
}
