<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression;

use Closure;
use Illuminate\Support\Str;
use SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Exceptions\CompressorFailed;
use SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Exceptions\CompressorStartFailed;
use SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Exceptions\DecompressionFailed;
use Symfony\Component\Process\Process;

/**
 * Decompressor.
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 11.05.2019
 * @version : 1.0
 */
class Decompressor extends BaseCompressor
{
    /**
     * The output directory.
     *
     * @var string
     */
    protected $outPath;

    /**
     * Whether or not existing files should be overwritten without prompting.
     *
     * @var bool
     */
    protected $overwriteExistingFiles;

    /**
     * Skip files that already exists.
     *
     * @var bool
     */
    protected $neverOverwriteExistingFiles;

    /**
     * Get a decompressor instance.
     *
     * @return Decompressor
     */
    public static function create(): self
    {
        return new static();
    }

    /**
     * Create an archive with the given file name from the given in path. You can optionally specify
     * a directory where the zip command should be executed in.
     *
     * @param string      $archive
     * @param string      $outPath
     * @param string|null $workingDirectory
     *
     * @return $this
     */
    public function make(string $archive, string $outPath, string $workingDirectory = null): self
    {
        $this->archive = $this->assertFileZipExtension($archive);
        $this->outPath = $outPath;

        if (filled($workingDirectory)) {
            $this->workingDirectory = $workingDirectory;
        }

        return $this;
    }

    /**
     * List all files of the given archive.
     *
     * @param string $archive
     *
     * @return array
     * @throws Exceptions\CompressorFailed
     */
    public function listFileNames(string $archive): array
    {
        $archive = $this->assertFileZipExtension($archive);
        $output = $this->runCommand("unzip -Z -1 $archive");

        return array_filter(explode("\n", trim($output)));
    }

    /**
     * List all files of the given archive except the given ones.
     *
     * @param string $archive
     * @param array  $except
     *
     * @return array
     * @throws Exceptions\CompressorFailed
     */
    public function listFileNamesExcept(string $archive, array $except): array
    {
        $except = implode(' ', $except);
        $archive = $this->assertFileZipExtension($archive);

        $output = $this->runCommand("unzip -Z -1 $archive -x $except");

        return array_filter(explode("\n", trim($output)));
    }

    /**
     * List all files of the given archive which are also present in needle.
     *
     * @param string $archive
     * @param array  $needle
     *
     * @return array
     * @throws Exceptions\CompressorFailed
     */
    public function listFileNamesIn(string $archive, array $needle)
    {
        $fileNames = $this->listFileNames($archive);

        return array_intersect($fileNames, $needle);
    }

    /**
     * Determine if the given archive is encrypted.
     *
     * @param string $archive
     *
     * @return bool
     * @throws Exceptions\CompressorFailed
     */
    public function isFileEncrypted(string $archive)
    {
        $archive = $this->assertFileZipExtension($archive);

        $output = $this->runCommand("unzip -Z -v $archive");

        $matches = [];
        if (! preg_match("/(file security status:)\W+((?:[a-zA-Z]*))/", $output, $matches)) {
            return false;
        }

        return $matches[2] === 'encrypted';
    }

    /**
     * Force the command to overwrite existing files without prompting.
     *
     * @return $this
     * @throws CompressorStartFailed
     */
    public function overwriteExistingFiles(): self
    {
        if ($this->neverOverwriteExistingFiles) {
            throw CompressorStartFailed::conflictingParameters(
                'overwriteExistingFiles',
                'neverOverwriteExistingFiles'
            );
        }

        $this->overwriteExistingFiles = true;

        return $this;
    }

    /**
     * Skip extraction of existing files.
     *
     * @return $this
     * @throws CompressorStartFailed
     */
    public function neverOverwriteExistingFiles(): self
    {
        if ($this->overwriteExistingFiles) {
            throw CompressorStartFailed::conflictingParameters(
                'neverOverwriteExistingFiles',
                'overwriteExistingFiles'
            );
        }

        $this->neverOverwriteExistingFiles = true;

        return $this;
    }

    /**
     * @return string
     * @throws CompressorFailed
     */
    public function run(): string
    {
        return $this->runCommand(
            $this->buildCommand(),
            Closure::fromCallable([$this, 'assertNoPasswordPrompting'])
        );
    }

    /**
     * Process callback to assert that no password prompt appears during execution.
     *
     * @param $type
     * @param $buffer
     *
     * @throws DecompressionFailed
     */
    protected function assertNoPasswordPrompting($type, $buffer)
    {
        if (Process::ERR === $type) {
            if (Str::contains($buffer, 'password:')) {
                throw DecompressionFailed::encryptionFlagMustBeSet();
            }
        }
    }

    /**
     * Validate the process and throw exceptions on failure.
     *
     * @throws CompressorFailed
     * @throws DecompressionFailed
     */
    protected function validateProcess()
    {
        if ($this->process->isSuccessful()) {
            return;
        }

        if ($this->hasArchiveNotFound($this->process)) {
            throw DecompressionFailed::archiveNotFound();
        }

        if ($this->hasNoPasswordProvided($this->process)) {
            throw DecompressionFailed::encryptionFlagMustBeSet();
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
    protected function hasArchiveNotFound(Process $process): bool
    {
        $output = $process->getErrorOutput();

        return preg_match("/(unzip:)\W*(cannot find or open).*/", $output) === 1;
    }

    /**
     * Determine if the msg contains an unable to get password error.
     *
     * @param Process $process
     *
     * @return bool
     */
    protected function hasNoPasswordProvided(Process $process): bool
    {
        $output = $process->getErrorOutput();

        return preg_match("/(skipping:)\W+([a-z]*.csv)\W+(unable to get password)/", $output) === 1;
    }

    /**
     * Build the command based on the given options.
     *
     * @return string
     */
    protected function buildCommand(): string
    {
        $this->command = 'unzip';

        $this->addPassword();
        $this->addOverwriteRule();
        $this->addArchive();
        $this->addOutPath();
        $this->addIncludes();
        $this->addExcludes();

        return $this->command;
    }

    /**
     * Add the out path to the command.
     */
    protected function addOutPath()
    {
        if (filled($this->outPath)) {
            $this->addToCommand("-d $this->outPath");
        }
    }

    /**
     * Add the existing file overwrite flags.
     */
    protected function addOverwriteRule()
    {
        $rule = '';

        if ($this->neverOverwriteExistingFiles && ! $this->overwriteExistingFiles) {
            $rule = '-n';
        }

        if ($this->overwriteExistingFiles && ! $this->neverOverwriteExistingFiles) {
            $rule = '-o';
        }

        if (filled($rule)) {
            $this->addToCommand($rule);
        }
    }
}
