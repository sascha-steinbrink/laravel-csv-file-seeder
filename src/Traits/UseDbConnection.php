<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Traits;

/**
 * Use db connection.
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 15.05.2019
 * @version : 1.0
 * @package SaschaSteinbrink\LaravelCsvFileSeeder\Traits
 */
trait UseDbConnection
{
    /**
     * The database connection to use for exporting.
     *
     * @var string
     */
    protected $connection = "";

    /**
     * @return string
     */
    public function getConnection(): string
    {
        return $this->connection;
    }

    /**
     * Set the database connection as string.
     *
     * @param null|string $connection
     */
    public function setConnection(?string $connection)
    {
        $this->connection = $this->assertConnection($connection);
    }

    /**
     * @return bool
     */
    public function hasConnection(): bool
    {
        return filled($this->connection);
    }

    /**
     * If the given connection is null get the default connection from the
     * database config file.
     *
     * @param null|string $connection
     *
     * @return string
     */
    protected function assertConnection(?string $connection)
    {
        if (!filled($connection)) {
            $connection = config('database.default');
        }

        return $connection;
    }
}