<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\DbHelper;
use SaschaSteinbrink\LaravelCsvFileSeeder\Traits\HasConfigFile;
use SaschaSteinbrink\LaravelCsvFileSeeder\Traits\UseCompression;
use SaschaSteinbrink\LaravelCsvFileSeeder\Traits\HasCommandUsage;
use SaschaSteinbrink\LaravelCsvFileSeeder\Traits\UseDbConnection;
use SaschaSteinbrink\LaravelCsvFileSeeder\Traits\ProcessesCsvFile;
use SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Compressor;

/**
 * CsvExporter.
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 11.05.2019
 * @version : 1.0
 */
class CsvExporter
{
    use HasConfigFile, HasCommandUsage, ProcessesCsvFile, UseCompression, UseDbConnection;

    /**
     * The created csv file paths.
     *
     * @var array
     */
    protected $writtenFileNames = [];

    /**
     * The total number of records written.
     *
     * @var int
     */
    protected $totalRecords = 0;

    /**
     * The total amount of bytes written.
     *
     * @var int
     */
    protected $totalBytes = 0;

    /**
     * The tables that should be ignored.
     *
     * @var array
     */
    protected $except = [];

    /**
     * Whether or not the csv files should contain the column names.
     *
     * @var bool
     */
    protected $withHeaders = true;

    /**
     * The number of items to be written into the csv file at a time.
     *
     * @var int
     */
    protected $exportChunkSize;

    /**
     * CsvExporter constructor.
     */
    public function __construct()
    {
        $this->readConfig();
    }

    /**
     * @return array
     */
    public function getWrittenFileNames(): array
    {
        return $this->writtenFileNames;
    }

    /**
     * @param array $writtenFileNames
     */
    public function setWrittenFileNames(array $writtenFileNames): void
    {
        $this->writtenFileNames = $writtenFileNames;
    }

    /**
     * @return int
     */
    public function getTotalRecords(): int
    {
        return $this->totalRecords;
    }

    /**
     * @return int
     */
    public function getTotalBytes(): int
    {
        return $this->totalBytes;
    }

    /**
     * @return array
     */
    public function getExcept(): array
    {
        return $this->except;
    }

    /**
     * @param array $except
     */
    public function setExcept(array $except): void
    {
        $this->except = $except;
    }

    /**
     * @return bool
     */
    public function isWithHeaders(): bool
    {
        return $this->withHeaders;
    }

    /**
     * @param bool $withHeaders
     */
    public function setWithHeaders(bool $withHeaders): void
    {
        $this->withHeaders = $withHeaders;
    }

    /**
     * @return int
     */
    public function getExportChunkSize(): int
    {
        return $this->exportChunkSize;
    }

    /**
     * @param int $exportChunkSize
     */
    public function setExportChunkSize(int $exportChunkSize)
    {
        $this->exportChunkSize = $exportChunkSize;
    }

    /**
     * Read the configuration file to set default values.
     */
    protected function readConfig()
    {
        $commandPrefix = 'commands.export_csv';

        $this->setConnection(config('database.default'));
        $this->dataPath = $this->readConfigValue('data_path', database_path('data'));
        $this->except = $this->readConfigValue('except', [], $commandPrefix);
        $this->withHeaders = $this->readConfigValue('with_headers', true, $commandPrefix);
        $this->delimiter = $this->readConfigValue('delimiter', ',');
        $this->enclosure = $this->readConfigValue('enclosure', '"');
        $this->escape = $this->readConfigValue('escape', '\\');
        $this->exportChunkSize = $this->readConfigValue('commands.export_csv.export_chunk_size', 100);
        $this->zipped = $this->readConfigValue('zipped', false);
        $this->archiveName = $this->readConfigValue('archive_name', 'csv-export.zip');
        $this->encrypted = $this->readConfigValue('encrypted', false);
        $this->encryptionPassword = $this->readConfigValue('encryption_password', 'secret');

        if ($this->zipped) {
            $this->archivePath = $this->dataPath;
            $this->dataPath = sys_get_temp_dir();
        }
    }

    /**
     * Run the csv export.
     *
     * @throws Helpers\Compression\Exceptions\CompressorStartFailed
     */
    public function run()
    {
        $this->assertDataDirectoryFound();

        if ($this->withHeaders) {
            $this->exportTablesWithHeaders();
        } else {
            $this->exportTables();
        }

        if ($this->zipped) {
            $this->createZipFile();
        }

        $this->successExit();
    }

    /**
     * Assert the data path is found.
     */
    protected function assertDataDirectoryFound()
    {
        $path = $this->zipped ? $this->archivePath : $this->dataPath;

        if (is_dir($path)) {
            return;
        }

        if ($path === config('laravel-csv-file-seeder.data_path')) {
            mkdir($path);

            return;
        }

        $this->error("The directory '$path' could not be found!");
        $this->errorExit();
    }

    /**
     * Export the table data.
     */
    protected function exportTables()
    {
        $tables = DbHelper::getTables($this->connection, $this->except);

        foreach ($tables as $tableName) {
            $this->exportTable($tableName);
        }
    }

    /**
     * Export the table data with headers.
     */
    protected function exportTablesWithHeaders()
    {
        $tables = DbHelper::getTablesWithColumns($this->connection, $this->except);

        foreach ($tables as $tableName => $columns) {
            $this->exportTable($tableName, $columns);
        }
    }

    /**
     * Export the data of the given table.
     *
     * @param string     $table
     * @param array|null $columns
     */
    protected function exportTable(string $table, ?array $columns = null)
    {
        if (! $this->hasTable($table)) {
            return;
        }

        $data = [];

        if (filled($columns)) {
            $data[] = $columns;
        }

        $this->writeChunked($table, $data);
    }

    /**
     * Determine if the schema has the given table.
     *
     * @param string $table
     *
     * @return bool
     */
    protected function hasTable(string $table): bool
    {
        if (! $hasTable = Schema::connection($this->connection)->hasTable($table)) {
            $this->warn("<comment>Table $table not found!", 'Export csv', 'v');
        }

        return $hasTable;
    }

    /**
     * Get the data for the given table.
     *
     * @param string     $table
     * @param array|null $columns
     *
     * @return void
     */
    protected function writeChunked(string $table, ?array $columns = null): void
    {
        $firstChunk = true;
        $this->openCsv("$table.csv");
        $path = $this->csvFile->getRealPath();
        $records = 0;

        $count = DB::connection($this->connection)->table($table)->count();

        if ($count > $this->exportChunkSize) {
            $this->createProgressBar($count);
        }

        if($columns === null) {
            $columns = DbHelper::getTableColumnListing($table, $this->connection);
        }

        DB::connection($this->connection)->table($table)->orderBy($columns[0])->chunk($this->exportChunkSize,
            function ($items) use (&$firstChunk, $columns, &$records) {
                $items = $this->mapChunkData($items);

                if ($firstChunk) {
                    $items = filled($columns) ? array_merge($columns, $items) : $items;
                    $firstChunk = false;
                }

                $chunkedRecords = $this->addChunk($items);
                $records += $chunkedRecords;

                if ($records === $chunkedRecords) {
                    $chunkedRecords--;
                }

                $this->advanceProgress($chunkedRecords);
            });

        if ($this->assertFileExists($path)) {
            $this->updateProgress($table, $path, $records);
        }

        $this->closeCsvFile();
    }

    /**
     * If the given value is null it will be changed to 'NULL' otherwise the value
     * itself will be returned.
     *
     * @param $value
     *
     * @return string
     */
    protected function stringifyNullValues($value)
    {
        if ($value === null) {
            $value = 'NULL';
        }

        return $value;
    }

    /**
     * Replace all null values with 'NULL' and return the given collection as an array.
     *
     * @param Collection $items
     *
     * @return array
     */
    protected function mapChunkData(Collection $items): array
    {
        return $items->map(function ($item) {
            $values = array_values((array) $item);

            return array_map([$this, 'stringifyNullValues'], $values);
        })->all();
    }

    /**
     * Insert the given chunk data into the csv file.
     *
     * @param array $data
     *
     * @return int
     */
    protected function addChunk(array $data): int
    {
        $records = 0;

        foreach ($data as $line => $row) {
            if ($this->addRow($row)) {
                $records++;
            }
        }

        return $records;
    }

    /**
     * Open the csv file.
     *
     * @param string $fileName
     */
    protected function openCsv(string $fileName)
    {
        $filePath = $this->getFilePath($fileName, $this->dataPath);

        try {
            $this->openCsvFile($fileName, $this->dataPath, 'w');
            $this->warn($filePath, 'Exporting csv');
        } catch (Exception $e) {
            $this->error("Could not open $filePath.");
            $this->errorExit();
        }
    }

    /**
     * Add the given row the current file.
     *
     * @param array $row
     *
     * @return bool
     */
    protected function addRow(array $row)
    {
        if ($this->csvFile->fputcsv($row)) {
            return true;
        }

        $rowString = implode(',', $row);
        $path = $this->csvFile->getRealPath();

        $this->warn(
            "Could not write the following row: $rowString into $path",
            'Exporting csv',
            'v'
        );

        return false;
    }

    /**
     * Assert that the given file path exists.
     *
     * @param string $path
     *
     * @return bool
     */
    protected function assertFileExists(string $path)
    {
        if (! ($written = file_exists($path))) {
            $this->warn("Could not write $path", 'Exporting csv', 'v');
        }

        return $written;
    }

    /**
     * Update the written file names, the total records and bytes written.
     *
     * @param string $table
     * @param string $path
     * @param int    $records
     */
    protected function updateProgress(string $table, string $path, int $records)
    {
        if ($this->withHeaders && $records > 0) {
            $records--;
        }

        $bytes = $this->csvFile->getSize();
        $this->writtenFileNames[] = "$table.csv";
        $this->totalBytes += $bytes;
        $this->totalRecords += $records;

        $this->success(
            "$records records written into $path ($bytes bytes)",
            'Exported csv',
            null,
            ' '
        );
    }

    /**
     * Create a zip file containing the exported csv files.
     *
     * @throws Helpers\Compression\Exceptions\CompressorStartFailed
     */
    protected function createZipFile()
    {
        $archivePath = $this->getFilePath($this->archiveName, $this->archivePath);

        $this->warn($archivePath, 'Compressing zip', 'v');
        $compressor = Compressor::create()
                                ->make($archivePath, '*.csv', $this->dataPath)
                                ->includeFiles($this->writtenFileNames);

        if ($this->encrypted) {
            $compressor->usePassword($this->encryptionPassword);
        }

        $this->debugCommand($compressor);

        try {
            $output = $compressor->run();
            $this->warn($output, null, 'vvv');
        } catch (Exception $e) {
            $this->error($e->getMessage());
            $this->errorExit();
        }

        $this->removeTempFiles($this->writtenFileNames);
        $this->success($archivePath, 'Compressed zip', 'v');
    }

    /**
     * Print the command being executed to the console.
     *
     * @param Compressor $compressor
     */
    protected function debugCommand(Compressor $compressor)
    {
        $compressorCmd = $compressor->getDumpCommand($this->isDebug());
        $this->warn($compressorCmd, 'Compressing zip', 'vv');
    }

    /**
     * Print an success message to the console after the export has finished.
     */
    protected function successExit()
    {
        $records = $this->totalRecords === 1 ? '1 record' : "$this->totalRecords records";
        $totalFiles = count($this->writtenFileNames);
        $files = $totalFiles === 1 ? '1 file' : "$totalFiles files";

        $this->success(
            'Csv exporting completed successfully. '.
            "Exported $records into $files ($this->totalBytes bytes)"
        );
    }

    /**
     * Exit the seeder with an error message.
     */
    protected function errorExit()
    {
        $this->closeCsvFile();
        $this->exit('Database export failed!');
    }
}
