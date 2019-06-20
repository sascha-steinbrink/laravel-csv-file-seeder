<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Commands;


use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use RuntimeException;
use Symfony\Component\Console\Input\InputOption;
use SaschaSteinbrink\LaravelCsvFileSeeder\LaravelCsvFileSeeder;

/**
 * CsvSeedCommand
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 11.05.2019
 * @version : 1.0
 * @package SaschaSteinbrink\LaravelCsvFileSeeder\Commands
 */
class CsvSeedCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'csv:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with records coming from csv files';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        $seeder = $this->makeSeeder();

        if ($this->shouldAskForPassword($seeder)) {
            $password = $this->askForEncryptionPassword();
            $this->setPassword($seeder, $password);
        }

        try {
            $seeder->run();
        } catch (RuntimeException $e) {
            $seeder->error($e->getMessage());
        }
    }

    /**
     * Returns true when the user wants to export an encrypted zip file.
     *
     * @param LaravelCsvFileSeeder $seeder
     *
     * @return bool
     */
    protected function shouldAskForPassword(LaravelCsvFileSeeder $seeder)
    {
        return $seeder->isZipped() && $seeder->isEncrypted();
    }

    /**
     * Set the given password if filled.
     *
     * @param LaravelCsvFileSeeder $seeder
     * @param null|string               $password
     */
    protected function setPassword(LaravelCsvFileSeeder $seeder, ?string $password)
    {
        if (filled($password)) {
            $seeder->setEncryptionPassword($password);
        }
    }

    /**
     * Ask the user for a password to use for encryption. If the input is empty the default password
     * from the config file will be used.
     *
     * @return mixed
     */
    protected function askForEncryptionPassword()
    {
        $fallback = config('laravel-csv-file-seeder.encryption_password', 'secret');

        return $this->secret(
            'What password to use for encryption (<comment>Leave empty to use the password from the config file!</comment>) ?',
            $fallback
        );
    }

    protected function makeSeeder(): LaravelCsvFileSeeder
    {
        $seeder = new LaravelCsvFileSeeder();
        $seeder->setCommand($this);
        $seeder->setConnection($this->option('connection'));
        $seeder->setDataPath($this->option('data-path'));
        $seeder->setConfigFiles($this->getFiles());
        $seeder->setDelimiter($this->option('delimiter'));
        $seeder->setEnclosure($this->option('enclosure'));
        $seeder->setEscape($this->option('escape'));
        $seeder->setShouldTrim($this->getBooleanValue('trim-values'));
        $seeder->setInsertChunkSize($this->option('insert-chunk-size'));
        $seeder->setTruncate($this->getBooleanValue('truncate'));
        $seeder->setForeignKeyChecks($this->getBooleanValue('foreign-key-checks'));
        $seeder->setZipped($this->getBooleanValue('zipped'));
        $seeder->setArchiveName($this->option('archive-name'));
        $seeder->setEncrypted($this->getBooleanValue('encrypted'));

        if ($seeder->isZipped()) {
            $seeder->setArchivePath($seeder->getDataPath());
            $seeder->setDataPath(sys_get_temp_dir());
        }

        return $seeder;
    }

    /**
     * Get the files to use for seeding.
     *
     * @return array|null
     */
    protected function getFiles()
    {
        $fileOption = $this->option('files');

        if (in_array($fileOption, [null, 'null'])) {
            return [];
        }

        return explode(",", $fileOption);
    }

    /**
     * Parse the given option key to a boolean.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function getBooleanValue(string $key): bool
    {
        return in_array($this->option($key), ['true', '1', true], true);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            [
                'data-path',
                '-p',
                InputOption::VALUE_OPTIONAL,
                'The folder the csv files are located',
                config('laravel-csv-file-seeder.data_path', database_path('data')),
            ],
            ['files', '-i', InputOption::VALUE_OPTIONAL, 'The files that should be seeded'],
            [
                'delimiter',
                '-d',
                InputOption::VALUE_OPTIONAL,
                'The delimiter character to use for parsing csv fields',
                config('laravel-csv-file-seeder.delimiter', ','),
            ],
            [
                'enclosure',
                '-l',
                InputOption::VALUE_OPTIONAL,
                'The enclosure character to use for parsing csv fields',
                config('laravel-csv-file-seeder.enclosure', '"'),
            ],
            [
                'escape',
                '-c',
                InputOption::VALUE_OPTIONAL,
                'The escape character to use for parsing csv fields',
                config('laravel-csv-file-seeder.escape', '\\'),
            ],
            [
                'trim-values',
                '-m',
                InputOption::VALUE_OPTIONAL,
                'Should trim leading or trailing white spaces from the csv fields',
                config('laravel-csv-file-seeder.trim_values', true),
            ],
            [
                'insert-chunk-size',
                '-s',
                InputOption::VALUE_OPTIONAL,
                'The number of rows to read before inserting',
                config('laravel-csv-file-seeder.insert_chunk_size', 50),
            ],
            [
                'truncate',
                '-t',
                InputOption::VALUE_OPTIONAL,
                'Whether or not the desired tables should be truncated before seeding',
                config('laravel-csv-file-seeder.truncate', true),
            ],
            [
                'foreign-key-checks',
                '-k',
                InputOption::VALUE_OPTIONAL,
                'Enable or disable foreign key checks while truncating',
                config('laravel-csv-file-seeder.foreign_key_checks', true),
            ],
            [
                'zipped',
                '-z',
                InputOption::VALUE_OPTIONAL,
                'Import data is a zip file.',
                config('laravel-csv-file-seeder.zipped', false),
            ],
            [
                'archive-name',
                '-a',
                InputOption::VALUE_OPTIONAL,
                'The archive name to import',
                config('laravel-csv-file-seeder.archive_name', 'csv-export.zip'),
            ],
            [
                'encrypted',
                '-e',
                InputOption::VALUE_OPTIONAL,
                'The zip file is encrypted.',
                config('laravel-csv-file-seeder.encrypted', false),
            ],
            [
                'connection',
                null,
                InputOption::VALUE_OPTIONAL,
                'The database connection to seed',
                config('laravel-csv-file-seeder.connection', 'mysql'),
            ],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }
}