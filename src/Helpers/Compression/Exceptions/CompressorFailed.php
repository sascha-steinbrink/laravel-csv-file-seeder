<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Exceptions;

use Exception;
use Symfony\Component\Process\Process;

/**
 * CompressionFailed.
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 15.05.2019
 * @version : 1.0
 */
class CompressorFailed extends Exception
{
    /**
     * @param Process $process
     *
     * @return CompressorFailed
     */
    public static function processFailed(Process $process)
    {
        $error = sprintf('The command "%s" failed.'."\n\nExit Code: %s(%s)\n\nWorking directory: %s",
            $process->getCommandLine(),
            $process->getExitCode(),
            $process->getExitCodeText(),
            $process->getWorkingDirectory()
        );

        return new static($error);
    }

    /**
     * @return CompressorFailed
     */
    public static function wrongPassword()
    {
        return new static('Wrong encryption password!');
    }
}
