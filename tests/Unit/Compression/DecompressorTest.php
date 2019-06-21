<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Tests\Unit\Compression;


use SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Decompressor;
use SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Exceptions\CompressorFailed;
use SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Exceptions\DecompressionFailed;

/**
 * DecompressorTest
 *
 * @author : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created: 13.06.2019
 * @version: 1.0
 * @package SaschaSteinbrink\LaravelCsvFileSeeder\Tests\Unit\Compression
 */
class DecompressorTest extends CompressionTestCase
{
    /** @test */
    function it_provides_a_factory_method()
    {
        $this->assertInstanceOf(Decompressor::class, Decompressor::create());
    }

    /** @test */
    function it_can_generate_an_unzip_command()
    {
        $actual = Decompressor::create()
                              ->make("test.zip", "*.csv")
                              ->getDumpCommand();

        $expected = "unzip test.zip -d *.csv";

        $this->assertSame($expected, $actual);
    }

    /** @test */
    function it_can_generate_an_unzip_command_with_encryption_and_hidden_password()
    {
        $actual = Decompressor::create()
                              ->make("test.zip", "*.csv")
                              ->usePassword('secret')
                              ->getDumpCommand();

        $expected = "unzip -P **** test.zip -d *.csv";

        $this->assertSame($expected, $actual);
    }

    /** @test */
    function it_can_generate_an_unzip_command_with_encryption_and_visible_password()
    {
        $actual = Decompressor::create()
                              ->make("test.zip", "*.csv")
                              ->usePassword('secret')
                              ->getDumpCommand(true);

        $expected = "unzip -P 'secret' test.zip -d *.csv";

        $this->assertSame($expected, $actual);
    }

    /** @test */
    function it_can_generate_an_unzip_command_with_excluding_files()
    {
        $actual = Decompressor::create()
                              ->make("test.zip", "*.csv")
                              ->excludeFiles(['users.csv'])
                              ->getDumpCommand();

        $expected = "unzip test.zip -d *.csv -x users.csv";

        $this->assertSame($expected, $actual);
    }

    /** @test */
    function it_can_generate_an_unzip_command_with_force_overwrite()
    {
        $actual = Decompressor::create()
                              ->make("test.zip", "*.csv")
                              ->overwriteExistingFiles()
                              ->getDumpCommand();

        $expected = "unzip -o test.zip -d *.csv";

        $this->assertSame($expected, $actual);
    }

    /** @test */
    function it_can_generate_an_unzip_command_with_never_overwrite()
    {
        $actual = Decompressor::create()
                              ->make("test.zip", "*.csv")
                              ->neverOverwriteExistingFiles()
                              ->getDumpCommand();

        $expected = "unzip -n test.zip -d *.csv";

        $this->assertSame($expected, $actual);
    }

    /** @test */
    function it_can_decompress_an_archive_from_a_given_directory()
    {
        $this->makeUnzip('export.zip')->run();

        $this->assertFileExists("$this->tmpPath/addresses.csv");
        $this->assertFileExists("$this->tmpPath/users.csv");
    }

    /** @test */
    function it_can_decompress_a_password_protected_archive()
    {
        $output = $this->makeUnzip("encrypted.zip")
                       ->usePassword("secret")
                       ->run();

        $this->assertRegExp("/((inflating|extracting):)\W+([\S]*.csv)/", $output);
    }

    /** @test */
    function it_throws_an_exception_when_a_wrong_password_is_provided()
    {
        $this->expectException(CompressorFailed::class);

        $this->makeUnzip("encrypted.zip")
             ->usePassword("wrong")
             ->run();
    }

    /** @test */
    function it_throws_an_exception_when_trying_to_decompress_an_encrypted_archive_without_encrypted_flag_enabled()
    {
        $this->expectException(DecompressionFailed::class);

        $this->makeUnzip("encrypted.zip")
            ->run();
    }

    /** @test */
    function it_throws_an_exception_when_the_archive_is_not_found()
    {
        $this->expectException(DecompressionFailed::class);

        $this->makeUnzip("not-found.zip")
            ->run();
    }

    /** @test */
    function it_can_exclude_specific_files_on_decompression()
    {
        $output = $this->makeUnzip("export.zip")
                       ->excludeFiles(['addresses.csv'])
                       ->run();

        $this->assertNotRegExp("/((inflating|extracting):)\W+([\S]*addresses.csv)/", $output);
    }

    /** @test */
    function it_can_overwrite_existing_files_without_prompting()
    {
        $this->makeUnzip("export.zip")->run();
        $output = $this->makeUnzip("export.zip")
                       ->overwriteExistingFiles()
                       ->run();

        $this->assertRegExp("/((inflating|extracting):)\W+([\S]*addresses.csv)/", $output);
        $this->assertRegExp("/((inflating|extracting):)\W+([\S]*users.csv)/", $output);
    }

    /** @test */
    function it_can_skip_existing_files_without_prompting()
    {
        $this->makeUnzip("export.zip")->run();
        $output = $this->makeUnzip("export.zip")
                       ->neverOverwriteExistingFiles()
                       ->run();

        $this->assertNotRegExp("/((inflating|extracting):)\W+([\S]*addresses.csv)/", $output);
        $this->assertNotRegExp("/((inflating|extracting):)\W+([\S]*users.csv)/", $output);
    }

    /** @test */
    function it_can_determine_if_an_archive_is_encrypted()
    {
        $encrypted = Decompressor::create()
                                 ->isFileEncrypted("$this->filePath/export.zip");

        $this->assertFalse($encrypted);

        $encrypted = Decompressor::create()
                                 ->isFileEncrypted("$this->filePath/encrypted.zip");

        $this->assertTrue($encrypted);
    }

    /** @test */
    function it_can_list_all_file_names_from_an_archive()
    {
        $files = Decompressor::create()
                             ->listFileNames("$this->filePath/export.zip");

        $this->assertCount(2, $files);
        $this->assertEquals(['addresses.csv', 'users.csv'], $files);
    }

    /** @test */
    function it_can_list_all_file_names_except_given_ones_from_an_archive()
    {
        $files = Decompressor::create()
                             ->listFileNamesExcept("$this->filePath/export.zip", ['addresses.csv']);

        $this->assertNotCount(2, $files);
        $this->assertEquals(['users.csv'], $files);
    }

    /** @test */
    function it_can_list_all_file_names_which_are_also_present_in_given_ones_from_an_archive()
    {
        $files = Decompressor::create()
                             ->listFileNamesIn("$this->filePath/export.zip", ['addresses.csv']);

        $this->assertCount(1, $files);
        $this->assertEquals(['addresses.csv'], $files);
    }
}