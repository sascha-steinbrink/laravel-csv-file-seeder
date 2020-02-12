<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Tests\Unit\Commands;


use Illuminate\Support\Str;
use Symfony\Component\Console\Tester\CommandTester;
use SaschaSteinbrink\LaravelCsvFileSeeder\Tests\DbTestCase;
use SaschaSteinbrink\LaravelCsvFileSeeder\Commands\CsvSeedCommand;

/**
 * CsvSeedCommandTest
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 18.05.2019
 * @version : 1.0
 * @package SaschaSteinbrink\LaravelCsvFileSeeder\Tests\Unit\Commands
 */
class CsvSeedCommandTest extends DbTestCase
{
    /**
     * @var CsvSeedCommand
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

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $tmp = $app['config']->get('test.file-path');
        $app['config']->set('laravel-csv-file-seeder.data_path', $tmp);
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->command = new CsvSeedCommand();
        $this->command->setLaravel(app());

        $this->commandTester = new CommandTester($this->command);

        $this->tables = [
            'address' => [
                'name' => 'addresses',
                'csv' => 2,
                'zip' => 1
            ],
            'user'    => [
                'name' => 'users',
                'csv' => 2,
                'zip' => 1
            ],
        ];
    }

    /** @test */
    function it_can_seed_the_database_using_configuration_file_settings()
    {
        $this->commandTester->execute([]);

        $this->assertCsvSeeded($this->tables['address'], $this->getOutput());
        $this->assertCsvSeeded($this->tables['user'], $this->getOutput());
        $this->assertSeedingSuccess(4, 2, $this->getOutput(), 2);
    }

    /** @test */
    function it_can_seed_the_database_from_a_given_data_path()
    {
        $this->commandTester->execute([
            '--data-path' => $this->filePath,
        ]);

        $this->assertCsvSeeded($this->tables['address'], $this->getOutput());
        $this->assertCsvSeeded($this->tables['user'], $this->getOutput());
        $this->assertSeedingSuccess(4,2, $this->getOutput(), 2);
    }

    /** @test */
    function it_shows_an_error_if_the_given_data_path_does_not_exists()
    {
        $path = "$this->tmpPath/not-exists";

        $this->commandTester->execute(['--data-path' => $path]);

        $this->assertStringContainsString("The directory '$path' could not be found!", $this->getOutput());
        $this->assertSeedingFailed($this->getOutput());
    }

    /** @test */
    function it_can_seed_the_database_including_only_given_file_names()
    {
        $this->commandTester->execute([
            '--files' => 'users.csv',
        ]);

        $this->assertCsvNotSeeded($this->tables['address'], $this->getOutput());
        $this->assertCsvSeeded($this->tables['user'], $this->getOutput());
        $this->assertSeedingSuccess(2,1, $this->getOutput());
    }

    /** @test */
    function it_can_seed_the_database_from_an_given_archive()
    {
        $this->commandTester->execute([
            '--zipped' => 'true',
            '--archive-name' => 'export',
        ]);

        $this->assertCsvSeeded($this->tables['address'], $this->getOutput(), true);
        $this->assertCsvSeeded($this->tables['user'], $this->getOutput(), true);
        $this->assertSeedingSuccess(2,2, $this->getOutput(), 2);
    }

    /** @test */
    function it_asks_for_a_password_if_encryption_is_enabled()
    {
        $input = [
            '--zipped'    => 'true',
            '--encrypted' => 'true',
        ];

        $this->artisan('csv:seed', $input)
             ->expectsQuestion(
                 'What password to use for encryption (<comment>Leave empty to use the password from the config file!</comment>) ?',
                 'abc123'
             )
             ->assertExitCode(0);
    }

    /** @test */
    function it_can_limit_the_sql_queries_fired_during_seeding_through_insert_chunk_size()
    {
        $this->commandTester->execute([
            '--insert-chunk-size' => 1
        ]);

        $this->assertCsvSeeded($this->tables['address'], $this->getOutput());
        $this->assertCsvSeeded($this->tables['user'], $this->getOutput());
        $this->assertSeedingSuccess(4,2, $this->getOutput(), 4);
    }

    protected function getOutput()
    {
        return $this->commandTester->getDisplay();
    }

    protected function assertCsvSeeded(array $table, string $output, bool $zipped = false)
    {
        $this->assertCsv($table, $output, $zipped);
    }

    protected function assertCsvNotSeeded(array $table, string $output, bool $zipped = false)
    {
        $this->assertCsv($table, $output, $zipped, true);
    }

    protected function assertCsv(array $table, string $output, bool $zipped, bool $negate = false)
    {
        $field = $zipped === true ? 'zip' : 'csv';
        $name = $table['name'];
        $rows = "{$table[$field]} " . Str::plural('row', $table[$field]);

        $assertion = $negate ? 'assertNotRegExp' : 'assertRegExp';

        $this->$assertion("/(Seeding csv:)\W+([\S]*$name.csv)/", $output);
        $this->$assertion(
            "/(Seeded csv: Inserted $rows into)\W+([\S]*$name)\W+(table)/",
            $output
        );
    }

    protected function assertSeedingSuccess(int $rows, int $tables, string $output, int $queries = 1)
    {
        $seededRows = "$rows " . Str::plural('row', $rows);
        $seededTables = "$tables " . Str::plural('table', $tables);
        $seededQueries = "$queries " . Str::plural('query', $queries);

        $this->assertStringContainsString(
            "Csv seeding completed successfully. Inserted $seededRows into $seededTables using $seededQueries.",
            $output
        );
    }

    protected function assertSeedingFailed(string $output)
    {
        $this->assertStringContainsString('Database seeding failed!', $output);
    }
}
