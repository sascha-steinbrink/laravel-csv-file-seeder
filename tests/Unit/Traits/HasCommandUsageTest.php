<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Tests\Unit\Traits;


use RuntimeException;
use Illuminate\Console\Command;
use Orchestra\Testbench\TestCase;
use SaschaSteinbrink\LaravelCsvFileSeeder\Traits\HasCommandUsage;

/**
 * HasCommandUsageTest
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 17.05.2019
 * @version : 1.0
 * @package SaschaSteinbrink\LaravelCsvFileSeeder\Tests\Unit\Traits
 */
class HasCommandUsageTest extends TestCase
{
    use HasCommandUsage;

    /** @test */
    function it_can_get_the_command()
    {
        $this->setCommand(new Command());
        $this->assertInstanceOf(Command::class, $this->getCommand());
    }

    /** @test */
    function it_can_determine_if_a_command_is_set()
    {
        $this->setCommand(new Command());
        $this->assertTrue($this->hasCommand());
    }

    /** @test */
    function it_can_exit_the_command()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("My message");

        $this->exit("My message");
    }
}