<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Unit\Traits;


use SaschaSteinbrink\LaravelCsvFileSeeder\Traits\UseDbConnection;
use Tests\TestCase;

/**
 * UseDbConnection
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 17.05.2019
 * @version : 1.0
 * @package SaschaSteinbrink\LaravelCsvFileSeeder\Unit\Traits
 */
class UseDbConnectionTest extends TestCase
{
    /** @test */
    function it_can_get_the_connection_string()
    {
        $testClass = new class { use UseDbConnection; };

        $testClass->setConnection('my-connection');
        $this->assertEquals('my-connection', $testClass->getConnection());
    }

    /** @test */
    function it_can_determine_if_a_connection_is_set()
    {
        $testClass = new class { use UseDbConnection; };
        $this->assertFalse($testClass->hasConnection());
    }

    /** @test */
    function it_can_assign_the_default_connection_if_null_is_provided()
    {
        $testClass = new class { use UseDbConnection; };
        $testClass->setConnection(null);

        $expected = config('database.default');
        $this->assertEquals($expected, $testClass->getConnection());
    }

    /** @test */
    function it_can_assign_the_default_connection_if_an_empty_string_is_provided()
    {
        $testClass = new class { use UseDbConnection; };
        $testClass->setConnection("");

        $expected = config('database.default');
        $this->assertEquals($expected, $testClass->getConnection());
    }
}