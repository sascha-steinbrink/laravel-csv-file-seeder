<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Traits;

use SplFileObject;

/**
 * Processes csv file.
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 11.05.2019
 * @version : 1.0
 */
trait ProcessesCsvFile
{
    use HasFileUsage;

    /**
     * The current csv file.
     *
     * @var SplFileObject
     */
    protected $csvFile = null;

    /**
     * The folder the csv files should be stored.
     *
     * @var string
     */
    protected $dataPath = '';

    /**
     * The csv file delimiter character to use for parsing csv fields.
     *
     * @var string
     */
    protected $delimiter = ',';

    /**
     * The csv file enclosure character to use for parsing csv fields.
     *
     * @var string
     */
    protected $enclosure = '"';

    /**
     * The csv file escape character to use for parsing csv fields.
     *
     * @var string
     */
    protected $escape = '\\';

    /**
     * @return string
     */
    public function getDataPath(): string
    {
        return $this->dataPath;
    }

    /**
     * @param string $dataPath
     */
    public function setDataPath(string $dataPath): void
    {
        $this->dataPath = $dataPath;
    }

    /**
     * @return string
     */
    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    /**
     * @param string $delimiter
     */
    public function setDelimiter(string $delimiter): void
    {
        $this->delimiter = $delimiter;
    }

    /**
     * @return string
     */
    public function getEnclosure(): string
    {
        return $this->enclosure;
    }

    /**
     * @param string $enclosure
     */
    public function setEnclosure(string $enclosure): void
    {
        $this->enclosure = $enclosure;
    }

    /**
     * @return string
     */
    public function getEscape(): string
    {
        return $this->escape;
    }

    /**
     * @param string $escape
     */
    public function setEscape(string $escape): void
    {
        $this->escape = $escape;
    }

    /**
     * Assert the file extension of the given file name to be .csv.
     *
     * @param string $fileName
     *
     * @return string
     */
    protected function assertCsvFileExtension(string $fileName)
    {
        return $this->assertFileExtension($fileName, '.csv');
    }

    /**
     * Open the given file in the given mode and set the csv control.
     *
     * @param string $fileName
     * @param string $path
     * @param string $mode
     */
    protected function openCsvFile(string $fileName, string $path, string $mode)
    {
        $fileName = $this->assertCsvFileExtension($fileName);
        $filePath = $this->getFilePath($fileName, $path);

        $this->makeFile($filePath, $mode);
    }

    /**
     * Create/open the csv file object.
     *
     * @param string $filePath
     * @param string $mode
     */
    protected function makeFile(string $filePath, string $mode)
    {
        $this->csvFile = new SplFileObject($filePath, $mode);
        $this->csvFile->setCsvControl(
            $this->delimiter,
            $this->enclosure,
            $this->escape
        );
        $this->csvFile->setFlags(
            SplFileObject::READ_CSV |
            SplFileObject::READ_AHEAD |
            SplFileObject::SKIP_EMPTY |
            SplFileObject::DROP_NEW_LINE
        );
    }

    /**
     * Close the current csv file.
     */
    protected function closeCsvFile()
    {
        $this->csvFile = null;
    }
}
