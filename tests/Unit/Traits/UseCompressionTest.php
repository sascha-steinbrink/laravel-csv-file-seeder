<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Tests\Unit\Traits;


use Orchestra\Testbench\TestCase;
use SaschaSteinbrink\LaravelCsvFileSeeder\Traits\UseCompression;

/**
 * UseCompressionTest
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 17.05.2019
 * @version : 1.0
 * @package SaschaSteinbrink\LaravelCsvFileSeeder\Tests\Unit\Traits
 */
class UseCompressionTest extends TestCase
{
    use UseCompression;

    /** @test */
    function it_can_get_the_zipped_state()
    {
        $this->setZipped(true);
        $this->assertTrue($this->isZipped());
    }

    /** @test */
    function it_can_get_the_archive_name()
    {
        $this->setArchiveName('test.zip');
        $this->assertEquals('test.zip', $this->getArchiveName());
    }

    /** @test */
    function it_can_get_the_archive_path()
    {
        $this->setArchivePath('my/archive/path');
        $this->assertEquals('my/archive/path', $this->getArchivePath());
    }

    /** @test */
    function it_can_get_the_encrypted_state()
    {
        $this->setEncrypted(true);
        $this->assertTrue($this->isEncrypted());
    }

    /** @test */
    function it_can_get_the_encryption_password()
    {
        $this->setEncryptionPassword('secret');
        $this->assertEquals('secret', $this->getEncryptionPassword());
    }
}