<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Unit\Traits;


use SaschaSteinbrink\LaravelCsvFileSeeder\Traits\HasConfigFile;
use Tests\TestCase;

/**
 * HasConfigFileTest
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 17.05.2019
 * @version : 1.0
 * @package SaschaSteinbrink\LaravelCsvFileSeeder\Unit\Traits
 */
class HasConfigFileTest extends TestCase
{
    use HasConfigFile;

    /** @test */
    function it_can_get_the_config_file_name()
    {
        $configFileName = 'my-config-file';
        $this->setConfigFileName($configFileName);
        $this->assertEquals($configFileName, $this->getConfigFileName());
    }

    /** @test */
    function it_can_get_the_input_configuration()
    {
        $inputConfig = ['key' => 'value'];
        $this->setInputConfig($inputConfig);
        $this->assertEquals($inputConfig, $this->getInputConfig());
    }

    /** @test */
    function it_can_read_a_value_from_a_given_config_file()
    {
        $configFileName = 'laravel-csv-file-seeder';
        $this->setConfigFileName($configFileName);

        $this->assertEquals(",", $this->readConfigValue('delimiter', "default"));
    }

    /** @test */
    function it_can_read_a_value_from_the_default_config_file_if_no_config_file_is_given()
    {
        $this->assertEquals(",", $this->readConfigValue('delimiter', "default"));
    }

    /** @test */
    function it_can_get_a_default_value_if_no_entry_in_the_config_file_is_found()
    {
        $configFileName = 'laravel-csv-file-seeder';
        $this->setConfigFileName($configFileName);

        $this->assertEquals("default", $this->readConfigValue('not-exist', "default"));
    }

    /** @test */
    function it_can_read_a_value_from_a_given_config_file_with_a_given_prefix()
    {
        $configFileName = 'laravel-csv-file-seeder';
        $prefix = 'commands.export_csv';
        $expected = [
            'migrations',
            'password_resets',
        ];

        $this->setConfigFileName($configFileName);
        $this->assertEquals(
            $expected,
            $this->readConfigValue('except', [], $prefix)
        );
    }

    /** @test */
    function it_can_get_a_default_value_with_a_given_prefix_if_no_entry_in_the_config_file_is_found()
    {
        $configFileName = 'laravel-csv-file-seeder';
        $prefix = 'commands.export_csv';
        $expected = [];

        $this->setConfigFileName($configFileName);
        $this->assertEquals(
            $expected,
            $this->readConfigValue('not-exist', [], $prefix)
        );
    }

    /** @test */
    function it_can_skip_a_value_from_config_file_if_the_key_exists_in_the_input_config()
    {
        $configFileName = 'laravel-csv-file-seeder';
        $inputConfig = ['delimiter' => 'value'];

        $this->setConfigFileName($configFileName);
        $this->setInputConfig($inputConfig);

        $this->assertEquals("value", $this->getConfigValue('delimiter', "default"));
    }
}