<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder;

use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Decompressor;
use SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Exceptions\CompressorFailed;
use SaschaSteinbrink\LaravelCsvFileSeeder\Traits\HasCommandUsage;
use SaschaSteinbrink\LaravelCsvFileSeeder\Traits\HasConfigFile;
use SaschaSteinbrink\LaravelCsvFileSeeder\Traits\ProcessesCsvFile;
use SaschaSteinbrink\LaravelCsvFileSeeder\Traits\UseCompression;
use SaschaSteinbrink\LaravelCsvFileSeeder\Traits\UseDbConnection;

/**
 * LaravelCsvFileSeeder.
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 11.05.2019
 * @version : 1.0
 */
class LaravelCsvFileSeeder extends Seeder
{
    use HasConfigFile, HasCommandUsage, ProcessesCsvFile, UseCompression, UseDbConnection;

    /**
     * The rows read from the csv which currently not inserted into the database.
     *
     * @var array
     */
    protected $rows;

    /**
     * The number rows in the rows array.
     *
     * @var int
     */
    protected $rowCount;

    /**
     * The total number of files seeded.
     *
     * @var int
     */
    protected $totalFiles = 0;

    /**
     * The total number of rows seeded.
     *
     * @var int
     */
    protected $totalRows = 0;

    /**
     * The total number of queries fired.
     *
     * @var int
     */
    protected $totalQueries = 0;

    /**
     * The files that should be seeded. If no files are given all files will be seeded in
     * alphabetically order.
     *
     * @var array
     */
    protected $configFiles;

    /**
     * Indicates if any leading or trailing white space should be trimmed
     * from the csv fields.
     *
     * @var bool
     */
    protected $shouldTrim;

    /**
     * The number of rows to read before inserting the data into the database.
     *
     * @var int
     */
    protected $insertChunkSize;

    /**
     * Whether or not the desired table should be truncated before seeding.
     *
     * @var bool
     */
    protected $truncate;

    /**
     * Indicates if the foreign key checks should be enabled/disabled while truncating.
     *
     * @var bool
     */
    protected $foreignKeyChecks;

    /**
     * The the default setting of auto_detect_line_endings.
     *
     * @var bool
     */
    protected $autoDetectLineEndingsDefault;

    /**
     * CsvFileSeeder constructor.
     */
    public function __construct()
    {
        $this->readConfig();
    }

    /**
     * @return array
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * @return int
     */
    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    /**
     * @return int
     */
    public function getTotalFiles(): int
    {
        return $this->totalFiles;
    }

    /**
     * @return int
     */
    public function getTotalRows(): int
    {
        return $this->totalRows;
    }

    /**
     * @return array
     */
    public function getConfigFiles(): array
    {
        return $this->configFiles;
    }

    /**
     * @param array $configFiles
     */
    public function setConfigFiles(array $configFiles): void
    {
        $this->configFiles = $configFiles;
    }

    /**
     * @return bool
     */
    public function isShouldTrim(): bool
    {
        return $this->shouldTrim;
    }

    /**
     * @param bool $shouldTrim
     */
    public function setShouldTrim(bool $shouldTrim): void
    {
        $this->shouldTrim = $shouldTrim;
    }

    /**
     * @return int
     */
    public function getInsertChunkSize(): int
    {
        return $this->insertChunkSize;
    }

    /**
     * @param int $insertChunkSize
     */
    public function setInsertChunkSize(int $insertChunkSize): void
    {
        $this->insertChunkSize = $insertChunkSize;
    }

    /**
     * @return bool
     */
    public function isTruncate(): bool
    {
        return $this->truncate;
    }

    /**
     * @param bool $truncate
     */
    public function setTruncate(bool $truncate): void
    {
        $this->truncate = $truncate;
    }

    /**
     * @return bool
     */
    public function isForeignKeyChecks(): bool
    {
        return $this->foreignKeyChecks;
    }

    /**
     * @param bool $foreignKeyChecks
     */
    public function setForeignKeyChecks(bool $foreignKeyChecks): void
    {
        $this->foreignKeyChecks = $foreignKeyChecks;
    }

    /**
     * @return bool
     */
    public function isAutoDetectLineEndingsDefault(): bool
    {
        return $this->autoDetectLineEndingsDefault;
    }

    /**
     * @param bool $autoDetectLineEndingsDefault
     */
    public function setAutoDetectLineEndingsDefault(bool $autoDetectLineEndingsDefault): void
    {
        $this->autoDetectLineEndingsDefault = $autoDetectLineEndingsDefault;
    }

    /**
     * Read the configuration file.
     */
    protected function readConfig()
    {
        $this->setConnection(config('database.default'));
        $this->dataPath = $this->readConfigValue('data_path', database_path('data'));
        $this->configFiles = $this->readConfigValue('files', []);
        $this->delimiter = $this->readConfigValue('delimiter', ',');
        $this->enclosure = $this->readConfigValue('enclosure', '"');
        $this->escape = $this->readConfigValue('escape', '\\');
        $this->shouldTrim = $this->readConfigValue('trim_values', true);
        $this->insertChunkSize = $this->readConfigValue('insert_chunk_size', 50);
        $this->truncate = $this->readConfigValue('truncate', true);
        $this->foreignKeyChecks = $this->readConfigValue('foreign_key_checks', true);
        $this->zipped = $this->readConfigValue('zipped', false);
        $this->archiveName = $this->readConfigValue('archive_name', 'csv-export.zip');
        $this->encrypted = $this->readConfigValue('encrypted', false);
        $this->encryptionPassword = $this->readConfigValue('encryption_password', 'secret');

        $this->archiveName = $this->assertFileExtension($this->archiveName, '.zip');

        if ($this->zipped) {
            $this->archivePath = $this->dataPath;
            $this->dataPath = sys_get_temp_dir();
        }
    }

    /**
     * Run the database seeds.
     *
     * @throws Helpers\Compression\Exceptions\CompressorFailed
     * @throws Helpers\Compression\Exceptions\CompressorStartFailed
     */
    public function run()
    {
        $this->assertDataDirectoryFound();

        if ($this->zipped) {
            $this->unzipFiles();
        }

        $this->activateAutoDetectLineEndings();
        $this->disableForeignKeyChecks();

        $files = $this->getFiles();

        foreach ($files as $fileName) {
            $table = $this->getTableName($fileName);

            if ($this->hasTable($table)) {
                $this->seedFile($table, $fileName);
            }
        }

        $this->resetAutoDetectLineEndingsSetting();
        $this->enableForeignKeyChecks();
        $this->successExit();
    }

    /**
     * Decompress the archive to the temp directory.
     *
     * @throws Helpers\Compression\Exceptions\CompressorStartFailed
     */
    protected function unzipFiles()
    {
        $archivePath = $this->getFilePath($this->archiveName, $this->archivePath);

        $this->warn($archivePath, 'Decompressing zip', 'v');
        $decompressor = Decompressor::create()
                                    ->make($archivePath, $this->dataPath)
                                    ->overwriteExistingFiles();

        if ($this->encrypted) {
            $decompressor->usePassword($this->encryptionPassword);
        }

        $this->debugCommand($decompressor);

        try {
            $output = $decompressor->run();
            $this->warn($output, null, 'vvv');
        } catch (Exception $e) {
            $this->error($e->getMessage());
            $this->errorExit(false);
        }

        $this->success($archivePath, 'Decompressed zip', 'v');
    }

    /**
     * Print the command being executed to the console.
     *
     * @param Decompressor $decompressor
     */
    protected function debugCommand(Decompressor $decompressor)
    {
        $decompressorCmd = $decompressor->getDumpCommand($this->isDebug());
        $this->warn($decompressorCmd, 'Decompressing zip', 'vv');
    }

    /**
     * Remove all temporary crated csv files.
     *
     * @throws Helpers\Compression\Exceptions\CompressorFailed
     */
    protected function clearTempFiles()
    {
        if (! $this->zipped) {
            return;
        }

        $this->removeTempFiles($this->getTempFiles());
    }

    /**
     * Get the temporary written csv files.
     *
     * @return array
     * @throws Helpers\Compression\Exceptions\CompressorFailed
     */
    protected function getTempFiles(): array
    {
        $archivePath = $this->getFilePath($this->archiveName, $this->archivePath);

        if ($this->hasConfigFiles()) {
            return Decompressor::create()->listFileNamesIn($archivePath, $this->configFiles);
        }

        return Decompressor::create()->listFileNames($archivePath);
    }

    /**
     * Print an success message to the console after the seeder has finished without any error.
     *
     * @throws Helpers\Compression\Exceptions\CompressorFailed
     */
    protected function successExit()
    {
        $this->clearTempFiles();

        $rows = "$this->totalRows ".Str::plural('row', $this->totalRows);
        $tables = "$this->totalFiles ".Str::plural('table', $this->totalFiles);
        $queries = "$this->totalQueries ".Str::plural('query', $this->totalQueries);

        $this->success("Csv seeding completed successfully. Inserted $rows into $tables using $queries.", 'CsvSeeder');
    }

    /**
     * Enable auto_detect_line_endings setting.
     */
    protected function activateAutoDetectLineEndings()
    {
        $this->autoDetectLineEndingsDefault = ini_get('auto_detect_line_endings');

        if (! $this->autoDetectLineEndingsDefault) {
            ini_set('auto_detect_line_endings', true);
        }
    }

    /**
     * Restore the auto_detect_line_endings setting to their default value.
     */
    protected function resetAutoDetectLineEndingsSetting()
    {
        if (! $this->autoDetectLineEndingsDefault) {
            ini_set('auto_detect_line_endings', $this->autoDetectLineEndingsDefault);
        }
    }

    /**
     * Strip UTF-8 BOM characters from the start of a string.
     *
     * Taken from https://github.com/Flynsarmy/laravel-csv-seeder/blob/master/src/Flynsarmy/CsvSeeder/CsvSeeder.php
     *
     * @param string $text
     *
     * @return string       String with BOM stripped
     */
    public function stripUtf8Bom(string $text): string
    {
        $bom = pack('H*', 'EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);

        return $text;
    }

    /**
     * Seed the given table with the data from the given file.
     *
     * @param string $table
     * @param string $fileName
     */
    protected function seedFile(string $table, string $fileName)
    {
        $this->truncateTable($table);
        $lineCount = $this->countCsvRows($fileName);
        $this->openCsv($fileName);

        $tableColumns = Schema::connection($this->connection)->getColumnListing($table);
        $rowCount = 0;
        $headers = [];
        $this->resetRows();
        $line = [];

        if (($lineCount - 1) > $this->insertChunkSize) {
            $this->createProgressBar($lineCount - 1);
        }

        while (! $this->csvFile->eof() && $line !== null) {
            if (($line = $this->getLine($rowCount)) === null) {
                continue;
            }

            if ($rowCount === 0) {
                $headers = $this->parseHeaders($line, $table);
                $rowCount++;
                continue;
            }

            if ($this->addRow($table, $line, $headers, $tableColumns)) {
                $rowCount++;
            }
        }

        $this->saveRows($table);
        $this->updateProgress($table, $rowCount);

        $this->closeCsvFile();
    }

    /**
     * Get the next line from the csv file.
     *
     * @param int $rowCount
     *
     * @return null|array
     */
    protected function getLine(int $rowCount)
    {
        $line = $this->csvFile->fgetcsv();

        if ($line === null && $rowCount === 0) {
            $path = $this->csvFile->getRealPath();

            $this->warn(
                "<comment>File $path is empty!</comment>",
                'Seeding csv',
                'v'
            );
        }

        return $line;
    }

    /**
     * Map the given line from the given table and add the result to the rows array.
     *
     * @param string $table
     * @param array  $line
     * @param array  $headers
     * @param array  $tableColumns
     *
     * @return bool
     */
    protected function addRow(string $table, array $line, array $headers, array $tableColumns)
    {
        if (empty($line) || $line[0] === null) {
            return false;
        }

        $line[0] = $this->stripUtf8Bom($line[0]);
        $row = $this->mapData($headers, $line, $tableColumns);
        $this->insertRow($table, $row);

        return true;
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
            $this->openCsvFile($fileName, $this->dataPath, 'r');
            $this->warn($filePath, 'Seeding csv');
        } catch (Exception $e) {
            $this->error("Could not open $filePath.");
            $this->errorExit();
        }
    }

    /**
     * Get the total rows of the given file.
     *
     * There is a known bug (46569) when using fgetcsv after seeking to a non zero position.
     * To work around this issue we use this method to open the file get the row count and finally close file.
     *
     * @param string $fileName
     *
     * @return int
     */
    protected function countCsvRows(string $fileName)
    {
        $this->openCsv($fileName);

        $this->csvFile->seek(PHP_INT_MAX);
        $lineCount = $this->csvFile->key();

        $this->closeCsvFile();

        return $lineCount;
    }

    /**
     * Update the total rows and total files written.
     *
     * @param string $table
     * @param int    $rowCount
     */
    protected function updateProgress(string $table, int $rowCount)
    {
        $rowCount = $rowCount === 0 ? $rowCount : $rowCount - 1;
        $rows = "$rowCount ".Str::plural('row', $rowCount);
        $this->success("Inserted $rows into $table table", 'Seeded csv', null, ' ');

        $this->totalRows += $rowCount;
        $this->totalFiles++;
    }

    /**
     * Parse the given line and return the headers.
     *
     * @param array  $line
     * @param string $table
     *
     * @return array
     */
    protected function parseHeaders(array $line, string $table): array
    {
        $headersCount = count($line);

        if ($headersCount === 0) {
            $this->warn(
                "<comment>No columns found for $table table.!</comment>",
                'Seeding csv',
                'v'
            );
        }

        if ($headersCount === 1) {
            $this->warn(
                "<comment>Only one column found for $table table. Maybe you have the wrong delimiter ($this->delimiter) if you expect more!</comment>",
                'Seeding csv',
                'v'
            );
        }

        return $line;
    }

    /**
     * Determine whether the given file is readable or not.
     *
     * @param string $file
     *
     * @return bool
     */
    protected function isFileReadable(string $file): bool
    {
        if (is_readable($file)) {
            return true;
        }

        $message = "The file '$file' could not be read!";

        $this->warn(
            "<comment>$message</comment>",
            'CsvSeeder',
            'v'
        );

        return false;
    }

    /**
     * Exit the seeder with an error message.
     *
     * @param bool $clearTempFiles
     */
    protected function errorExit(bool $clearTempFiles = true)
    {
        $this->closeCsvFile();

        if ($clearTempFiles) {
            try {
                $this->clearTempFiles();
            } catch (CompressorFailed $e) {
                $this->warn('Could not clear temp files!', null, 'vvv');
            }
        }

        $this->enableForeignKeyChecks();
        $this->exit('Database seeding failed!');
    }

    /**
     * Initialize the row array to an empty array and set the row count to zero.
     */
    protected function resetRows()
    {
        $this->rows = [];
        $this->rowCount = 0;
    }

    /**
     * Add the given row into the rows array. If the number of rows reaches the
     * insert chunk size insert them into database and reset the rows and row count.
     *
     * @param string $table
     * @param array  $row
     */
    protected function insertRow(string $table, array $row)
    {
        $this->rows[] = $row;
        $this->rowCount++;

        if ($this->rowCount === $this->insertChunkSize) {
            $this->saveRows($table);
        }
    }

    /**
     * Insert the rows for the given table into the database and reset the rows
     * and the row count.
     *
     * @param string $table
     */
    protected function saveRows(string $table)
    {
        if ($this->rowCount > 0) {
            try {
                DB::connection($this->connection)->table($table)->insert($this->rows);
                $this->totalQueries++;
                $rows = Str::plural('row', $this->rowCount);

                $this->advanceProgress($this->rowCount);

                $this->warn("[$this->totalQueries] Saved $this->rowCount $rows into $table table!",
                    'Chunk insert',
                    'vvv',
                    ' '
                );
            } catch (Exception $e) {
                $this->handleException(
                    $e,
                    "Error while inserting into <comment>$table</comment> table:",
                    'Inserting',
                    ['duplicate-entry', 'foreign-key-checks']
                );
            }
        }

        $this->resetRows();
    }

    /**
     * Get the table name from the given file name.
     *
     * @param string $fileName
     *
     * @return string
     */
    protected function getTableName(string $fileName): string
    {
        return explode('.', $fileName)[0];
    }

    /**
     * Determine if the given table exists.
     *
     * @param string $table
     *
     * @return bool
     */
    protected function hasTable(string $table): bool
    {
        if (Schema::connection($this->connection)->hasTable($table)) {
            return true;
        }

        $this->warn("Table $table not found! The file $table.csv will be omitted!",
            'CsvSeeder',
            'v'
        );

        return false;
    }

    /**
     * Map the given data to the given headers if they exists on the table.
     *
     * @param array $headers
     * @param array $data
     * @param array $tableColumns
     *
     * @return array
     */
    protected function mapData(array $headers, array $data, array $tableColumns): array
    {
        $result = [];

        foreach ($headers as $index => $value) {
            if (in_array($value, $tableColumns)) {
                $result[$value] = $this->getDataValue($data[$index]);
            }
        }

        return $result;
    }

    /**
     * If the given value contains the string 'NULL' null will be returned
     * otherwise the value itself.
     *
     * @param $value
     *
     * @return null|string
     */
    protected function getDataValue($value): ?string
    {
        if ($value === 'NULL') {
            return null;
        }

        return $this->shouldTrim ? trim($value) : $value;
    }

    /**
     * Determine if a configuration array is given.
     *
     * @return bool
     */
    protected function hasConfigFiles(): bool
    {
        return filled($this->configFiles);
    }

    /**
     * Get all csv file that should be called from the seeder.
     *
     * @return array
     */
    protected function getFiles(): array
    {
        $files = scandir($this->dataPath, 1);

        $csvFiles = array_filter($files, function ($file) {
            return $this->isValidCsvFile($file);
        });

        if ($this->hasConfigFiles()) {
            $this->assertCustomFiles($csvFiles);
        }

        return $csvFiles;
    }

    /**
     * Assert the data path is found.
     */
    protected function assertDataDirectoryFound()
    {
        $path = $this->zipped ? $this->archivePath : $this->dataPath;

        if (! is_dir($path)) {
            $this->error("The directory '$path' could not be found!");
            $this->errorExit();
        }
    }

    /**
     * Assert that the given user files are found in the given data path. A warning will be
     * printed to the console with all files that couldn't be found.
     *
     * @param array $files
     */
    protected function assertCustomFiles(array $files)
    {
        $filesNotFound = array_diff($this->configFiles, $files);
        $notFoundCount = count($filesNotFound);

        if ($notFoundCount > 0) {
            $fileTxt = $notFoundCount === 1 ? 'file' : 'files';
            $fileNames = implode(', ', $filesNotFound);
            $this->warn("The $fileTxt $fileNames could not be found!", 'CsvSeeder', 'v');
        }
    }

    /**
     * Determine if the given file is a csv file.
     *
     * @param string $file
     *
     * @return bool
     */
    protected function isValidCsvFile(string $file): bool
    {
        $filePath = $this->getFilePath($file, $this->dataPath);

        if (is_dir($filePath)) {
            if (! in_array($file, ['.', '..'])) {
                $this->warn(
                    "<comment>File could not be processed! Directory given: '$file'</comment>",
                    'CsvSeeder',
                    'vvv'
                );
            }

            return false;
        }

        if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'csv') {
            $this->warn(
                "<comment>File could not be processed! No valid csv file given: '$file'</comment>",
                'CsvSeeder',
                'vvv'
            );

            return false;
        }

        if ($this->hasConfigFiles()) {
            return in_array($file, $this->configFiles);
        }

        return true;
    }

    /**
     * Truncate the given table if enabled.
     *
     * @param string $table
     */
    protected function truncateTable(string $table)
    {
        if (! $this->truncate) {
            return;
        }

        try {
            DB::connection($this->connection)->table($table)->truncate();
        } catch (Exception $e) {
            $this->handleException(
                $e,
                "Error while truncating <comment>$table</comment> table:",
                'Truncating',
                ['foreign-key-checks']
            );
        }
    }

    /**
     * Disable foreign key checks if disabled.
     */
    protected function disableForeignKeyChecks()
    {
        if (! $this->foreignKeyChecks) {
            Schema::connection($this->connection)
                  ->disableForeignKeyConstraints();
        }
    }

    /**
     * Enable foreign key checks if disabled.
     */
    protected function enableForeignKeyChecks()
    {
        if (! $this->foreignKeyChecks) {
            Schema::connection($this->connection)
                  ->enableForeignKeyConstraints();
        }
    }

    /**
     * Parse the given exception to human readable error message.
     *
     * @param Exception $e
     * @param string    $message
     * @param string    $task
     * @param array     $hints
     */
    protected function handleException(Exception $e, string $message, string $task, array $hints = [])
    {
        $msg = $e->getMessage();

        $this->error($message, $task);
        $this->error($msg, null);
        $this->displayHints($hints, $msg);
        $this->errorExit();
    }

    /**
     * Iterate over the given hints and display them to the user if the message
     * contains an error that belongs to the hint.
     *
     * @param array  $possibleHints
     * @param string $msg
     */
    protected function displayHints(array $possibleHints, string $msg)
    {
        if (! filled($possibleHints)) {
            return;
        }

        foreach ($possibleHints as $hint) {
            $this->hints($hint, $msg);
        }
    }

    /**
     * Determine if the msg contains and incorrect password error.
     *
     * @param string $msg
     *
     * @return bool
     */
    protected function hasIncorrectPassword(string $msg): bool
    {
        return preg_match("/(skipping:)\W+([a-z]*.csv)\W+(incorrect password)/", $msg) === 1;
    }

    /**
     * Determine if the msg contains an access violation (1701) error.
     *
     * @param string $msg
     *
     * @return bool
     */
    protected function hasAccessViolationTruncateTable(string $msg): bool
    {
        $needle = 'Syntax error or access violation: 1701 Cannot truncate a table referenced in a foreign key constraint';

        return Str::contains($msg, $needle);
    }

    /**
     * Determine if the msg contains an integrity constraint violation (1452) error.
     *
     * @param string $msg
     *
     * @return bool
     */
    protected function hasIntegrityViolationAddOrUpdateRow(string $msg): bool
    {
        return Str::contains($msg, 'Integrity constraint violation');
    }

    /**
     * If the message contains an foreign key constraint (1701) issue show a hint to the user
     * on how to avoid this issue.
     *
     * @param string $msg
     */
    protected function shouldDisplayForeignKeyChecksInfo(string $msg)
    {
        if ($this->hasAccessViolationTruncateTable($msg) || $this->hasIntegrityViolationAddOrUpdateRow($msg)) {
            $this->info(
                "To avoid foreign key constraint issues you can disable the 'foreign_key_checks' ".
                "option globally in the config file or you can set the '--foreign-key-checks' option to false!",
                'Truncating'
            );
        }
    }

    /**
     * Determine if the msg contains an integrity violation (1062) error.
     *
     * @param string $msg
     *
     * @return bool
     */
    protected function hasIntegrityConstraintDuplicateEntry(string $msg): bool
    {
        $needle = 'Integrity constraint violation: 1062 Duplicate entry';

        return Str::contains($msg, $needle);
    }

    /**
     * If the message contains an integrity constraint (1062) error show a hint to the user
     * on how to avoid this issue.
     *
     * @param string $msg
     */
    protected function shouldDisplayDuplicateEntryInfo(string $msg)
    {
        if ($this->hasIntegrityConstraintDuplicateEntry($msg)) {
            $this->info(
                "To avoid duplicate entries you can enable the 'truncate' option in the config file!",
                'Inserting'
            );
        }
    }

    /**
     * If the given message contains the given error display a hint to the user on
     * how to avoid this error.
     *
     * @param string $error
     * @param string $msg
     */
    protected function hints(string $error, string $msg)
    {
        switch ($error) {
            case 'foreign-key-checks':
                $this->shouldDisplayForeignKeyChecksInfo($msg);
                break;
            case 'duplicate-entry':
                $this->shouldDisplayDuplicateEntryInfo($msg);
                break;
        }
    }
}
