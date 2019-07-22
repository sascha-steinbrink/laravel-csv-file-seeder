<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Helpers;

use InvalidArgumentException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DbHelper.
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 11.05.2019
 * @version : 1.0
 */
class DbHelper
{
    /**
     * If the given connection is null get the default connection from the
     * database config file.
     *
     * @param null|string $connection
     *
     * @return string
     */
    protected static function assertConnection(?string $connection): string
    {
        if ($connection === null) {
            $connection = config('database.default');
        }

        return $connection;
    }

    /**
     * Get a list of tables for the given connection based on the connection driver.
     *
     * @param string $connection
     *
     * @return array
     */
    protected static function getTableList(string $connection)
    {
        $driver = DB::connection($connection)->getDriverName();

        switch ($driver) {
            case 'sqlite':
                return DB::connection($connection)
                         ->table('sqlite_master')
                         ->select('name')
                         ->orderBy('name')
                         ->get()
                         ->toArray();
            case 'mysql':
                return DB::connection($connection)->select('SHOW TABLES');
            case 'pgsql':
                return DB::connection($connection)
                         ->table('pg_catalog.pg_tables')
                         ->select('name')
                         ->orderBy('name')
                         ->get()
                         ->toArray();
            case 'sqlsrv':
                return DB::connection($connection)
                         ->table('INFORMATION_SCHEMA.TABLES')
                         ->select('name')
                         ->orderBy('name')
                         ->get()
                         ->toArray();
            default:
                throw new InvalidArgumentException("The driver '$driver' is not supported!");
        }
    }

    /**
     * Get all table names for the given connection. If no connection is given the
     * default connection will be used.
     *
     * @param string|null $connection The connection to get the tables from.
     * @param array       $ignore     A list of tables to ignore.
     *
     * @return array
     */
    public static function getTables(string $connection = null, array $ignore = []): array
    {
        $connection = self::assertConnection($connection);

        $tableList = self::getTableList($connection);
        $tables = [];

        foreach ($tableList as $table) {
            foreach ($table as $key => $value) {
                if (! in_array($value, $ignore)) {
                    $tables[] = $value;
                }
            }
        }

        return $tables;
    }

    /**
     * Get all table names except the given ones from the default connection.
     *
     * @param array $except A list of tables to ignore.
     *
     * @return array
     *
     * @see DbHelper::getTables()   if you want to get the table names from a connection
     *                              other than the default.
     */
    public static function getTablesExcept(array $except): array
    {
        return self::getTables(null, $except);
    }

    /**
     * Get a list with all columns per table for the given tables. Tables that are not
     * present in the schema will be ignored.
     *
     * @param array       $tables
     * @param null|string $connection
     *
     * @return array
     */
    public static function getTableColumnMapping(array $tables, ?string $connection = null): array
    {
        $connection = self::assertConnection($connection);
        $tableColumnMapping = [];

        foreach ($tables as $table) {
            $columns = self::getTableColumnListing($table, $connection);

            if ($columns !== null) {
                $tableColumnMapping[$table] = $columns;
            }
        }

        return $tableColumnMapping;
    }

    /**
     * Get the columns for the given table on the given connection. If no connection is given the
     * the default connection will be used. If the table does not exists null will be returned.
     *
     * @param string      $table
     * @param string|null $connection
     *
     * @return array|null
     */
    public static function getTableColumnListing(string $table, ?string $connection = null) {
        $connection = self::assertConnection($connection);

        if (!Schema::connection($connection)->hasTable($table)) {
            return null;
        }

        return Schema::connection($connection)->getColumnListing($table);
    }

    /**
     * Get a list with all columns per table for the given connection. If no connection is given the
     * default connection will be used.
     *
     * @param string|null $connection The connection to get the tables from.
     * @param array       $ignore     A list of tables to ignore.
     *
     * @return array
     */
    public static function getTablesWithColumns(string $connection = null, array $ignore = []): array
    {
        $tables = self::getTables($connection, $ignore);

        return self::getTableColumnMapping($tables, $connection);
    }

    /**
     * Get a list with all columns per table except the given tables for the default connection.
     *
     * @param array $ignore A list of tables to ignore.
     *
     * @return array
     *
     * @see DbHelper::getTablesWithColumns()   if you want to get a list of all columns per table
     *                                         from a connection other than the default.
     */
    public static function getTablesWithColumnsExcept(array $ignore)
    {
        return self::getTablesWithColumns(null, $ignore);
    }
}
