<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\tests\Unit\Compression;


use Illuminate\Support\Str;
use SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Compressor;
use SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Exceptions\CompressionFailed;
use SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\Compression\Exceptions\CompressorStartFailed;

/**
 * CompressorTest
 *
 * @author : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created: 13.06.2019
 * @version: 1.0
 */
class CompressorTest extends CompressionTestCase
{
    /** @test */
    function it_provides_a_factory_method()
    {
        $this->assertInstanceOf(Compressor::class, Compressor::create());
    }

    /** @test */
    function it_can_determine_if_it_has_been_executed()
    {
        $compressor = Compressor::create()
                                ->make("export.zip", "*.csv");

        $this->assertFalse($compressor->hasBeenExecuted(), "Has not been executed.");
    }

    /** @test */
    function it_will_throw_an_exception_if_include_files_gets_called_with_exclude_files()
    {
        $this->expectException(CompressorStartFailed::class);

        Compressor::create()
                  ->includeFiles(['include.csv'])
                  ->excludeFiles(['exclude.csv']);
    }

    /** @test */
    function it_will_throw_an_exception_if_exclude_files_gets_called_with_include_files()
    {
        $this->expectException(CompressorStartFailed::class);

        Compressor::create()
                  ->excludeFiles(['exclude.csv'])
                  ->includeFiles(['include.csv']);
    }

    /** @test */
    function it_can_generate_a_zip_command_for_one_file()
    {
        $actual = Compressor::create()
                            ->make("export.zip", "users.csv")
                            ->getDumpCommand();

        $expected = "zip export.zip users.csv";

        $this->assertSame($expected, $actual);
    }

    /** @test */
    function it_can_generate_a_zip_command_for_one_directory()
    {
        $actual = Compressor::create()
                            ->make("export.zip", "*.csv")
                            ->getDumpCommand();

        $expected = "zip export.zip *.csv";

        $this->assertSame($expected, $actual);
    }

    /** @test */
    function it_can_generate_a_zip_command_with_encryption_and_hidden_password()
    {
        $actual = Compressor::create()
                            ->make("export.zip", "users.csv")
                            ->usePassword('secret')
                            ->getDumpCommand();

        $expected = "zip --encrypt -P **** export.zip users.csv";

        $this->assertSame($expected, $actual);
    }

    /** @test */
    function it_can_generate_a_zip_command_with_encryption_and_visible_password()
    {
        $actual = Compressor::create()
                            ->make("export.zip", "users.csv")
                            ->usePassword('secret')
                            ->getDumpCommand(true);

        $expected = "zip --encrypt -P 'secret' export.zip users.csv";

        $this->assertSame($expected, $actual);
    }

    /** @test */
    function it_can_generate_a_zip_command_with_excluding_files()
    {
        $actual = Compressor::create()
                            ->make("export.zip", "users.csv")
                            ->excludeFiles(['users.csv'])
                            ->getDumpCommand();

        $expected = "zip export.zip users.csv -x users.csv";

        $this->assertSame($expected, $actual);
    }

    /** @test */
    function it_can_generate_a_zip_command_with_including_files()
    {
        $actual = Compressor::create()
                            ->make("export.zip", "users.csv")
                            ->includeFiles(['users.csv'])
                            ->getDumpCommand();

        $expected = "zip export.zip users.csv -i users.csv";

        $this->assertSame($expected, $actual);
    }

    /** @test */
    function it_can_create_a_zip_file_from_a_given_directory()
    {
        $output = $this->makeZip()
                       ->run();

        $path = Str::substr($this->filePath, 1);
        $this->assertFileExists("$this->tmpPath/export.zip");
        $this->assertContains("adding: $path/addresses.csv", $output);
        $this->assertContains("adding: $path/users.csv", $output);
    }

    /** @test */
    function it_can_create_a_password_protected_zip_file()
    {
        $output = $this->makeZip()
                       ->usePassword("secret")
                       ->run();

        $path = Str::substr($this->filePath, 1);
        $this->assertFileExists("$this->tmpPath/export.zip", "File exists");
        $this->assertContains("adding: $path/addresses.csv", $output, "Contains addresses.csv");
        $this->assertContains("adding: $path/users.csv", $output, "Contains users.csv");
    }

    /** @test */
    function it_can_exclude_specific_files_on_compression()
    {
        $output = $this->makeZip()
                       ->excludeFiles(["$this->filePath/users.csv"])
                       ->run();

        $path = Str::substr($this->filePath, 1);
        $this->assertContains("adding: $path/addresses.csv", $output, "Contains addresses.csv");
        $this->assertNotContains("adding: $path/users.csv", $output, "Not contains users.csv");
    }

    /** @test */
    function it_can_include_specific_files_on_compression()
    {
        $output = $this->makeZip()
                       ->includeFiles(["$this->filePath/addresses.csv"])
                       ->run();

        $path = Str::substr($this->filePath, 1);
        $this->assertContains("adding: $path/addresses.csv", $output, "Contains addresses.csv");
        $this->assertNotContains("adding: $path.csv", $output, "Not contains users.csv");
    }

    /** @test */
    function it_throws_an_exception_if_the_archive_is_empty()
    {
        $this->expectException(CompressionFailed::class);

        $this->makeZip()
             ->includeFiles(['hidden.csv', 'hidden2.csv'])
             ->run();
    }

    protected function removeLeadingSlash($path)
    {
        return Str::substr($path, 1);
    }
}