<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Unit\Traits;


use SaschaSteinbrink\LaravelCsvFileSeeder\tests\FileTestCase;
use SaschaSteinbrink\LaravelCsvFileSeeder\Traits\HasFileUsage;

/**
 * HasFileUsageTest
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 17.05.2019
 * @version : 1.0
 * @package SaschaSteinbrink\LaravelCsvFileSeeder\Unit\Traits
 */
class HasFileUsageTest extends FileTestCase
{
    use HasFileUsage;

    /** @test */
    function it_can_generate_a_file_path()
    {
        $expected = '/my/custom/path/myfile.txt';

        $this->assertEquals($expected, $this->getFilePath('myfile.txt', '/my/custom/path'));
    }

    /** @test */
    function it_can_assert_a_given_file_extension()
    {
        $this->assertEquals('myfile.txt', $this->assertFileExtension('myfile', '.txt'));
    }

    /** @test */
    function it_can_assert_a_given_file_extension_with_an_extension_missing_the_dot()
    {
        $this->assertEquals('myfile.txt', $this->assertFileExtension('myfile', 'txt'));
    }

    /** @test */
    function it_can_remove_given_file_names_from_a_given_path()
    {
        copy("$this->filePath/addresses.csv", "$this->tmpPath/addresses.csv");

        $this->removeFiles(['addresses.csv'], $this->tmpPath);

        $this->assertFalse(file_exists("$this->tmpPath/addresses.csv"));
    }

    /** @test */
    function it_can_ignore_missing_files_when_removing_given_file_names_from_a_given_path()
    {
        copy("$this->filePath/addresses.csv", "$this->tmpPath/addresses.csv");

        $this->removeFiles(['addresses.csv', 'missing.csv'], $this->tmpPath);

        $this->assertFalse(file_exists("$this->tmpPath/addresses.csv"));
    }
}