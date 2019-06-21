<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Commands;

use RuntimeException;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use SaschaSteinbrink\LaravelCsvFileSeeder\CsvExporter;
use SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Exceptions\CompressorStartFailed;

/**
 * CsvExportCommand.
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 11.05.2019
 * @version : 1.0
 */
class CsvExportCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'csv:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export the database into csv files';

    /**
     * Should the export be zipped.
     *
     * @var bool
     */
    protected $zipped;

    /**
     * Should the export be encrypted.
     *
     * @var
     */
    protected $encrypted;

    /**
     * Execute the console command.
     *
     * @return void
     * @throws CompressorStartFailed
     */
    public function handle()
    {
        $exporter = $this->makeCsvExporter();

        if ($this->shouldAskForPassword($exporter)) {
            $password = $this->askForEncryptionPassword();
            $this->setPassword($exporter, $password);
        }

        try {
            $exporter->run();
        } catch (RuntimeException $e) {
            $exporter->error($e->getMessage());
        }
    }

    /**
     * Returns true when the user wants to export an encrypted zip file.
     *
     * @param CsvExporter $exporter
     *
     * @return bool
     */
    protected function shouldAskForPassword(CsvExporter $exporter)
    {
        return $exporter->isZipped() && $exporter->isEncrypted();
    }

    /**
     * Set the given password if filled.
     *
     * @param CsvExporter $exporter
     * @param null|string      $password
     */
    protected function setPassword(CsvExporter $exporter, ?string $password)
    {
        if (filled($password)) {
            $exporter->setEncryptionPassword($password);
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

    /**
     * Initialize the csv exporter with the given options.
     *
     * @return CsvExporter
     */
    protected function makeCsvExporter(): CsvExporter
    {
        $exporter = new CsvExporter();
        $exporter->setCommand($this);
        $exporter->setConnection($this->option('connection'));
        $exporter->setDataPath($this->option('data-path'));
        $exporter->setZipped($this->getBooleanValue('zipped'));
        $exporter->setArchiveName($this->option('archive-name'));
        $exporter->setEncrypted($this->getBooleanValue('encrypted'));
        $exporter->setExcept($this->getExcept());
        $exporter->setWithHeaders($this->getBooleanValue('with-headers'));
        $exporter->setDelimiter($this->option('delimiter'));
        $exporter->setEnclosure($this->option('enclosure'));
        $exporter->setEscape($this->option('escape'));

        if ($exporter->isZipped()) {
            $exporter->setArchivePath($exporter->getDataPath());
            $exporter->setDataPath(sys_get_temp_dir());
        }

        return $exporter;
    }

    /**
     * Get the tables that should be ignored.
     *
     * @return array|null
     */
    protected function getExcept()
    {
        $fileOption = $this->option('except');

        if (in_array($fileOption, [null, 'null'])) {
            return [];
        }

        return explode(',', $fileOption);
    }

    /**
     * Parse the given key to an boolean value.
     *
     * @param string $key
     *
     * @return bool|null
     */
    protected function getBooleanValue(string $key)
    {
        $option = $this->option($key);

        return in_array($option, ['true', '1', true], true);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            [
                'data-path',
                '-p',
                InputOption::VALUE_OPTIONAL,
                'The folder the csv files should be stored',
                config('laravel-csv-file-seeder.data_path', database_path('data')),
            ],
            [
                'except',
                '-x',
                InputOption::VALUE_OPTIONAL,
                'The tables that should be ignored',
                implode(',', config('laravel-csv-file-seeder.commands.export_csv.except', [])),
            ],
            [
                'with-headers',
                '-w',
                InputOption::VALUE_OPTIONAL,
                'Whether or not the csv files should contain the column names',
                config('laravel-csv-file-seeder.commands.export_csv.with_headers', true),
            ],

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
                'zipped',
                '-z',
                InputOption::VALUE_OPTIONAL,
                'Export as an zip archive',
                config('laravel-csv-file-seeder.zipped', false),
            ],
            [
                'archive-name',
                '-a',
                InputOption::VALUE_OPTIONAL,
                'The archive name to use for saving',
                config('laravel-csv-file-seeder.archive_name', 'csv-export.zip'),
            ],
            [
                'encrypted',
                '-e',
                InputOption::VALUE_OPTIONAL,
                'Encrypt the exported zip archive',
                config('laravel-csv-file-seeder.encrypted', false),
            ],
            [
                'connection',
                null,
                InputOption::VALUE_OPTIONAL,
                'The database connection to export',
                config('laravel-csv-file-seeder.connection', config('database.default')),
            ],
        ];
    }
}
