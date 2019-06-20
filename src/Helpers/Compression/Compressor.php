<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression;


use SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Exceptions\CompressionFailed;
use SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Exceptions\CompressorFailed;
use Symfony\Component\Process\Process;

/**
 * Compressor.
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 11.05.2019
 * @version : 1.0
 * @package SaschaSteinbrink\LaravelCsvFileSeeder
 */
class Compressor extends BaseCompressor
{
    /**
     * The in path which could be a directory or a file path.
     *
     * @var string
     */
    protected $inPath;

    /**
     * Get a compressor instance.
     *
     * @return Compressor
     */
    public static function create(): Compressor
    {
        return new static();
    }

    /**
     * Create an archive with the given file name from the given in path. You can optionally specify
     * a directory where the zip command should be executed in.
     *
     * @param string      $archive
     * @param string      $inPath
     * @param string|null $workingDirectory
     *
     * @return $this
     */
    public function make(string $archive, string $inPath, string $workingDirectory = null): Compressor
    {
        $this->archive = $this->assertFileZipExtension($archive);
        $this->inPath = $inPath;

        if (filled($workingDirectory)) {
            $this->workingDirectory = $workingDirectory;
        }

        return $this;
    }

    /**
     * Validate the process and throw exceptions on failure.
     *
     * @throws CompressionFailed
     * @throws CompressorFailed
     */
    protected function validateProcess()
    {
        if($this->process->isSuccessful() && $this->hasZipEmpty($this->process)) {
            throw CompressionFailed::zipFileEmpty();
        }
        if(!file_exists($this->archive)) {
            throw CompressionFailed::zipFileNotCreated();
        }

        parent::validateProcess();
    }

    /**
     * Determine if the msg contains an file not found error.
     *
     * @param Process $process
     *
     * @return bool
     */
    protected function hasZipEmpty(Process $process): bool
    {
        $output = $process->getOutput();

        return preg_match("/(zip warning: zip file empty)/", $output) === 1;
    }

    /**
     * Build the command based on the given options.
     *
     * @return string
     */
    protected function buildCommand(): string
    {
        $this->command = 'zip';

        $this->addEncryption();
        $this->addPassword();
        $this->addArchive();
        $this->addInPath();
        $this->addIncludes();
        $this->addExcludes();

        return $this->command;
    }

    /**
     * Add the encrypt option to the command.
     */
    protected function addEncryption()
    {
        if ($this->encrypt) {
            $this->addToCommand("--encrypt");
        }
    }

    /**
     * Add the in path to the command.
     */
    protected function addInPath()
    {
        if (filled($this->inPath)) {
            $this->addToCommand($this->inPath);
        }
    }
}