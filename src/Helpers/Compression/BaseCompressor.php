<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression;

use Symfony\Component\Process\Process;
use SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Exceptions\CompressorFailed;
use SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Exceptions\CompressorStartFailed;

/**
 * BaseCompressor.
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 11.05.2019
 * @version : 1.0
 */
abstract class BaseCompressor
{
    /**
     * The process that runs the shell command.
     *
     * @var Process
     */
    protected $process;

    /**
     * The command to be executed.
     *
     * @var string
     */
    protected $command;

    /**
     * The archive name.
     *
     * @var string
     */
    protected $archive = 'zipped';

    /**
     * The files to include.
     *
     * @var array
     */
    protected $files;

    /**
     * The files to exclude.
     *
     * @var array
     */
    protected $excludeFiles;

    /**
     * Whether or not create an encrypted zip file.
     *
     * @var bool
     */
    protected $encrypt = false;

    /**
     * The password to use for encryption.
     *
     * @var string
     */
    protected $password;

    /**
     * The directory where the command should be executed.
     *
     * @var null|string
     */
    protected $workingDirectory = null;

    /**
     * Determine if the process has been executed.
     *
     * @return bool
     */
    public function hasBeenExecuted(): bool
    {
        return $this->process !== null;
    }

    /**
     * Get the process or null if it has not been executed yet.
     *
     * @return null|Process
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * The working directory where the zip command should be executed.
     *
     * @param string $directory
     *
     * @return BaseCompressor
     */
    public function workingDirectory(string $directory): self
    {
        $this->workingDirectory = $directory;

        return $this;
    }

    /**
     * Execute the command and get the output on success. On failure it will
     * thrown an exception.
     *
     * @return string
     * @throws CompressorFailed
     */
    public function run(): string
    {
        return $this->runCommand($this->buildCommand());
    }

    /**
     * Include only the given files from the in path.
     *
     * @param array $files
     *
     * @return $this
     * @throws CompressorStartFailed
     */
    public function includeFiles(array $files): self
    {
        if (filled($this->excludeFiles)) {
            throw CompressorStartFailed::conflictingParameters('includeFiled', 'excludeFiles');
        }

        $this->files = $files;

        return $this;
    }

    /**
     * Exclude the given files from the in path.
     *
     * @param array $files
     *
     * @return $this
     * @throws CompressorStartFailed
     */
    public function excludeFiles(array $files): self
    {
        if (filled($this->files)) {
            throw CompressorStartFailed::conflictingParameters('excludeFiles', 'includeFiles');
        }

        $this->excludeFiles = $files;

        return $this;
    }

    /**
     * Use the given password for de-/encryption.
     *
     * @param string $password
     *
     * @return $this
     */
    public function usePassword(string $password): self
    {
        $this->password = escapeshellarg($password);
        $this->encrypt = true;

        return $this;
    }

    /**
     * Get the command line that should be executed.
     *
     * @param bool $showPassword
     *
     * @return string
     */
    public function getDumpCommand(bool $showPassword = false): string
    {
        if ($showPassword) {
            return $this->buildCommand();
        }

        $password = $this->password;
        $this->password = '****';

        $command = $this->buildCommand();
        $this->password = $password;

        return $command;
    }

    /**
     * Start a process and run the given command. The method returns the command
     * output on success otherwise it will thrown an exception.
     *
     * @param string        $command
     * @param callable|null $callback
     *
     * @return string
     * @throws CompressorFailed
     */
    protected function runCommand(string $command, ?callable $callback = null)
    {
        $this->process = Process::fromShellCommandline($command, $this->workingDirectory);
        $this->process->run($callback);

        $this->validateProcess();

        return $this->process->getOutput();
    }

    /**
     * Validate the process and throw exceptions on failure.
     *
     * @throws CompressorFailed
     */
    protected function validateProcess()
    {
        if ($this->process->isSuccessful()) {
            return;
        }

        if ($this->hasIncorrectPassword($this->process)) {
            throw CompressorFailed::wrongPassword();
        }

        throw CompressorFailed::processFailed($this->process);
    }

    /**
     * Determine if the msg contains an incorrect password error.
     *
     * @param Process $process
     *
     * @return bool
     */
    protected function hasIncorrectPassword(Process $process): bool
    {
        $output = $process->getErrorOutput();

        return preg_match("/(skipping:)\W+([a-z]*.csv)\W+(incorrect password)/", $output) === 1;
    }

    /**
     * Assert the file extension of the given file name to be .zip.
     *
     * @param string $fileName
     *
     * @return string
     */
    protected function assertFileZipExtension(string $fileName)
    {
        if (ends_with($fileName, '.zip')) {
            return $fileName;
        }

        return "$fileName.zip";
    }

    /**
     * Build the command based on the given options.
     *
     * @return string
     */
    abstract protected function buildCommand(): string;

    /**
     * Add the destination file to the command.
     */
    protected function addArchive()
    {
        if (filled($this->archive)) {
            $this->addToCommand($this->archive);
        }
    }

    /**
     * Add the password if set and encryption is enabled.
     */
    protected function addPassword()
    {
        if (filled($this->password) && $this->encrypt) {
            $this->addToCommand("-P $this->password");
        }
    }

    /**
     * If specific include files are given add them to the command.
     */
    protected function addIncludes()
    {
        if (filled($this->files)) {
            $files = implode(' ', $this->files);
            $this->addToCommand("-i $files");
        }
    }

    /**
     * If specific exclude files are given add them to the command.
     */
    protected function addExcludes()
    {
        if (filled($this->excludeFiles)) {
            $files = implode(' ', $this->excludeFiles);
            $this->addToCommand("-x $files");
        }
    }

    /**
     * Append the given argument to the command.
     *
     * @param string $argument
     */
    protected function addToCommand(string $argument)
    {
        $this->command = "$this->command $argument";
    }
}
