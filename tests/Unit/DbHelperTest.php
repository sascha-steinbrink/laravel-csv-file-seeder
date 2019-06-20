<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\tests\Unit;


use SaschaSteinbrink\LaravelCsvFileSeeder\Helpers\DbHelper;
use SaschaSteinbrink\LaravelCsvFileSeeder\tests\DbTestCase;


/**
 * DbHelperTest
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 11.05.2019
 * @version : 1.0
 * @package SaschaSteinbrink\LaravelCsvFileSeeder\tests\Unit
 */
class DbHelperTest extends DbTestCase
{
    /** @test */
    function it_can_list_all_tables_from_a_given_connection()
    {
        $tables = DbHelper::getTables('sqlite_testing');

        $this->assertCount(4, $tables);
    }

    /** @test */
    function it_can_list_all_tables_from_the_default_connection()
    {
        $tables = DbHelper::getTables();

        $this->assertCount(4, $tables);
    }

    /** @test */
    function it_can_list_all_tables_except_given_ones()
    {
        $tables = DbHelper::getTablesExcept(['sqlite_sequence', 'users_email_unique']);
        $expected = [
            'addresses',
            'users',
        ];

        $this->assertCount(2, $tables);
        $this->assertEquals($expected, $tables);
    }

    /** @test */
    function it_can_list_all_columns_per_table_for_given_table_names()
    {
        $tables = ['addresses', 'users'];
        $expected = [
            "addresses" => [
                "id",
                "user_id",
                "zip",
                "city",
                "street",
                "created_at",
                "updated_at",
            ],
            "users"     => [
                "id",
                "name",
                "email",
                "email_verified_at",
                "password",
                "remember_token",
                "created_at",
                "updated_at",
            ],
        ];

        $columnMapping = DbHelper::getTableColumnMapping($tables);
        $this->assertEquals($expected, $columnMapping);
    }

    /** @test */
    function it_can_list_all_columns_per_table_for_given_table_names_and_skip_unknown_tables()
    {
        $tables = ['addresses', 'users', 'unknowns'];
        $columnMapping = DbHelper::getTableColumnMapping($tables);

        $this->assertCount(2, $columnMapping);
    }

    /** @test */
    function it_can_list_all_columns_per_table_for_given_table_names_except_given_tables()
    {
        $columnMapping = DbHelper::getTablesWithColumnsExcept(['sqlite_sequence']);

        $this->assertCount(2, $columnMapping);
    }
}