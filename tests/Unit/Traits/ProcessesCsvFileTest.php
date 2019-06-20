<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Unit\Traits;


use SaschaSteinbrink\LaravelCsvFileSeeder\tests\FileTestCase;
use SaschaSteinbrink\LaravelCsvFileSeeder\Traits\ProcessesCsvFile;

/**
 * ProcessesCsvFileTest
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 17.05.2019
 * @version : 1.0
 * @package SaschaSteinbrink\LaravelCsvFileSeeder\Unit\Traits
 */
class ProcessesCsvFileTest extends FileTestCase
{
    use ProcessesCsvFile;

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->csvFile = null;
    }

    /** @test */
    function it_can_get_the_delimiter()
    {
        $this->setDelimiter(';');
        $this->assertEquals(';', $this->getDelimiter());
    }

    /** @test */
    function it_can_get_the_enclosure()
    {
        $this->setEnclosure('"');
        $this->assertEquals('"', $this->getEnclosure());
    }

    /** @test */
    function it_can_get_the_escape()
    {
        $this->setEscape('\\');
        $this->assertEquals('\\', $this->getEscape());
    }

    /** @test */
    function it_can_get_the_data_path()
    {
        $this->setDataPath('/my/data/path');
        $this->assertEquals('/my/data/path', $this->getDataPath());
    }

    /** @test */
    function it_can_assert_a_file_name_has_a_csv_file_extension()
    {
        $this->assertEquals('myfile.csv', $this->assertCsvFileExtension('myfile'));
    }

    /** @test */
    function it_can_open_the_csv_file_with_a_given_path()
    {
        $this->openCsvFile('test.csv', $this->tmpPath, 'w');
        $this->assertTrue(file_exists("$this->tmpPath/test.csv"));
    }

    /** @test */
    function it_can_open_the_csv_file_with_the_given_options()
    {
        $this->setDelimiter('|');
        $this->setEnclosure("!");
        $this->setEscape("'");

        $this->openCsvFile('test.csv', $this->tmpPath, 'w');

        $expected = ['|', "!", "'"];
        $this->assertEquals($expected, $this->csvFile->getCsvControl());
    }

    /** @test */
    function it_can_open_the_csv_file_with_the_necessary_flags()
    {
        $this->openCsvFile('test.csv', $this->tmpPath, 'w');

        $this->assertEquals(15, $this->csvFile->getFlags());
    }

    /** @test */
    function it_can_close_the_csv_file()
    {
        $this->openCsvFile('test.csv', $this->tmpPath, 'w');
        $this->closeCsvFile();

        $this->assertNull($this->csvFile);
    }
}