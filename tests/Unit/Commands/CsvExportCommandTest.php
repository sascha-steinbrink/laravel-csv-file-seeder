<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Tests\Unit\Commands;

function sys_get_temp_dir()
{
    return __DIR__ . '/../../tmp';
}


use SaschaSteinbrink\LaravelCsvFileSeeder\Commands\CsvExportCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Output\OutputInterface;
use SaschaSteinbrink\LaravelCsvFileSeeder\Tests\DbTestCase;


/**
 * CsvExportCommandTest
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 18.05.2019
 * @version : 1.0
 * @package SaschaSteinbrink\LaravelCsvFileSeeder\Tests\Unit\Commands
 */
class CsvExportCommandTest extends DbTestCase
{
    /**
     * @var CsvExportCommand
     */
    protected $command;

    /**
     * @var CommandTester
     */
    protected $commandTester;

    /**
     * @var array
     */
    protected $tables;

    public function setUp(): void
    {
        parent::setUp();

        $this->command = new CsvExportCommand();
        $this->command->setLaravel(app());

        $this->commandTester = new CommandTester($this->command);

        $this->tables = [
            'address' => [
                'name'  => 'addresses',
                'bytes' => 141,
            ],
            'user'    => [
                'name'  => 'users',
                'bytes' => 265,
            ],
        ];
    }

    /** @test */
    function it_can_export_data_from_tables_using_configuration_file_settings()
    {
        $this->commandTester->execute([]);

        $this->assertCsvExported($this->tables['address'], $this->getOutput());
        $this->assertCsvExported($this->tables['user'], $this->getOutput());
        $this->assertExportSuccess(2, 406, $this->getOutput());
    }

    /** @test */
    function it_can_export_data_from_tables_except_given_ones()
    {
        $this->commandTester->execute(['--except' => 'sqlite_sequence,addresses']);

        $this->assertCsvNotExported($this->tables['address'], $this->getOutput());
        $this->assertCsvExported($this->tables['user'], $this->getOutput());
        $this->assertExportSuccess(1, $this->tables['user']['bytes'], $this->getOutput());
    }

    /** @test */
    function it_can_export_data_to_an_zip_archive()
    {
        $input = [
            '--except' => 'sqlite_sequence,addresses',
            '--zipped' => 'true',
        ];

        $this->commandTester->execute($input);

        $this->assertCsvExported($this->tables['user'], $this->getOutput());
        $this->assertExportSuccess(1, $this->tables['user']['bytes'], $this->getOutput());
        $this->assertFileExists("$this->tmpPath/db-csv-export.zip");
    }

    /** @test */
    function it_shows_a_compressing_message_when_exporting_data_to_an_zip_archive_in_verbose_mode()
    {
        $input = [
            '--except' => 'sqlite_sequence,addresses',
            '--zipped' => 'true',
        ];

        $this->commandTester->execute($input, ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $this->assertCsvExported($this->tables['user'], $this->getOutput());
        $this->assertExportSuccess(1, $this->tables['user']['bytes'], $this->getOutput());
        $this->assertZip('db-csv-export', $this->getOutput());
        $this->assertFileExists("$this->tmpPath/db-csv-export.zip");
    }

    /** @test */
    function it_can_export_data_to_an_zip_archive_of_a_given_name()
    {
        $archive = 'test.zip';
        $input = [
            '--except'       => 'sqlite_sequence,addresses',
            '--zipped'       => 'true',
            '--archive-name' => $archive,
        ];

        $this->commandTester->execute($input);

        $this->assertCsvExported($this->tables['user'], $this->getOutput());
        $this->assertExportSuccess(1, $this->tables['user']['bytes'], $this->getOutput());
        $this->assertFileExists("$this->tmpPath/$archive");
    }

    /** @test */
    function it_can_export_data_to_an_zip_archive_at_a_specified_folder()
    {
        $path = "$this->tmpPath/sub";
        mkdir($path);

        $input = [
            '--except'    => 'sqlite_sequence,addresses',
            '--zipped'    => 'true',
            '--data-path' => $path,
        ];

        $this->commandTester->execute($input);

        $this->assertCsvExported($this->tables['user'], $this->getOutput());
        $this->assertExportSuccess(1, $this->tables['user']['bytes'], $this->getOutput());
        $this->assertFileExists("$path/db-csv-export.zip");
    }

    /** @test */
    function it_asks_for_a_password_if_encryption_is_enabled()
    {
        $input = [
            '--except'    => 'sqlite_sequence,addresses',
            '--zipped'    => 'true',
            '--encrypted' => 'true',
        ];

        $this->artisan('csv:export', $input)
             ->expectsQuestion(
                 'What password to use for encryption (<comment>Leave empty to use the password from the config file!</comment>) ?',
                 'abc123'
             )
             ->assertExitCode(0);
    }

    /** @test */
    function it_shows_an_error_if_the_given_data_path_does_not_exists()
    {
        $path = "$this->tmpPath/not-exists";

        $this->commandTester->execute(['--data-path' => $path]);

        $this->assertContains("The directory '$path' could not be found!", $this->getOutput());
        $this->assertExportFailed($this->getOutput());
    }

    protected function getOutput()
    {
        return $this->commandTester->getDisplay();
    }

    protected function assertCsvExported(array $table, string $output)
    {
        $this->assertCsv($table, $output);
    }

    protected function assertCsvNotExported(array $table, string $output)
    {
        $this->assertCsv($table, $output, true);
    }

    protected function assertCsv(array $table, string $output, bool $negate = false)
    {
        $name = $table['name'];
        $bytes = $table['bytes'];

        $assertion = $negate ? 'assertNotRegExp' : 'assertRegExp';

        $this->$assertion("/(Exporting csv:)\W+([\S]*$name.csv)/", $output);
        $this->$assertion(
            "/(Exported csv: 1 records written into)\W+([\S]*$name.csv)\W+($bytes bytes)/",
            $output
        );
    }

    protected function assertZip(string $name, string $output, bool $negate = false)
    {
        $assertion = $negate ? 'assertNotRegExp' : 'assertRegExp';

        $this->$assertion("/(Compressing zip:)\W+([\S]*$name.zip)/", $output);
        $this->$assertion("/(Compressed zip:)\W+([\S]*$name.zip)/", $output);
    }

    protected function assertExportSuccess(int $count, int $bytes, string $output)
    {
        $exported = "$count record into $count file";
        if ($count > 1) {
            $exported = "$count records into $count files";
        }

        $this->assertContains(
            "Csv exporting completed successfully. Exported $exported ($bytes bytes)",
            $output
        );
    }

    protected function assertExportFailed(string $output)
    {
        $this->assertContains('Database export failed!', $output);
    }
}