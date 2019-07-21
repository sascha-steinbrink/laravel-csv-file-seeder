<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Database connection
    |--------------------------------------------------------------------------
    |
    | The database connection to use for seeding.
    |
    */
    'connection'          => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Data path
    |--------------------------------------------------------------------------
    |
    | The folder the csv files are located. It defaults to database/data.
    |
    */
    'data_path'           => database_path('data'),

    /*
    |--------------------------------------------------------------------------
    | Delimiter character
    |--------------------------------------------------------------------------
    |
    | The csv delimiter to use for parsing csv fields.
    |
    */
    'delimiter'           => ',',

    /*
    |--------------------------------------------------------------------------
    | Enclosure character
    |--------------------------------------------------------------------------
    |
    | The csv enclosure to use for parsing csv fields.
    |
    */
    'enclosure'           => '"',

    /*
    |--------------------------------------------------------------------------
    | Escape character
    |--------------------------------------------------------------------------
    |
    | The csv escape to use for parsing csv fields.
    |
    */
    'escape'              => '\\',

    /*
    |--------------------------------------------------------------------------
    | Trim csv values
    |--------------------------------------------------------------------------
    |
    | Indicates if any leading or trailing white space should be trimmed
    | from the csv fields.
    |
    */
    'trim_values'         => true,

    /*
    |--------------------------------------------------------------------------
    | Insert chunk size.
    |--------------------------------------------------------------------------
    |
    | Set the number of rows to read before an insert query will be executed.
    | This will limit the sql queries fired by the seeder and speed up the
    | performance.
    |
    | NOTE: If you have a large amount of data a small chunk size can cause
    |       the seeder to fail because of the large number of insert queries.
    |
    */
    'insert_chunk_size'   => 50,

    /*
    |--------------------------------------------------------------------------
    | Truncate tables
    |--------------------------------------------------------------------------
    |
    | Whether or not the desired tables should be truncated before seeding.
    |
    | NOTE: This will only affect tables where seeder data is available for!
    |       All other tables will be untouched!
    */
    'truncate'            => true,

    /*
    |--------------------------------------------------------------------------
    | Foreign key checks
    |--------------------------------------------------------------------------
    |
    | Enable or disable foreign key checks while truncating.
    |
    */
    'foreign_key_checks'  => true,

    /*
    |--------------------------------------------------------------------------
    | Files to run by the seeder
    |--------------------------------------------------------------------------
    |
    | The files that should be seeded in the given order.
    |
    | If no files are given all files located in the 'data_path' folder will
    | be seeded in alphabetically order.
    |
    */
    'files'               => [],

    /*
    |--------------------------------------------------------------------------
    | Zipped import/export
    |--------------------------------------------------------------------------
    |
    | Whether or not export/import an zip archive containing the csv files.
    |
    */
    'zipped'              => false,

    /*
    |--------------------------------------------------------------------------
    | Archive name
    |--------------------------------------------------------------------------
    |
    | The archive name to use.
    |
    */
    'archive_name'        => 'db-csv-export.zip',

    /*
    |--------------------------------------------------------------------------
    | Encrypted zip archive
    |--------------------------------------------------------------------------
    |
    | Whether or not the exported/imported zip archive should be/is password
    | protected.
    |
    */
    'encrypted'           => false,

    /*
    |--------------------------------------------------------------------------
    | Encryption password
    |--------------------------------------------------------------------------
    |
    | The password to use when encryption is enabled.
    |
    */
    'encryption_password' => env('CSV_SEEDER_ENCRYPTION_PASSWORD', 'secret'),

    /*
    |--------------------------------------------------------------------------
    | Command specific configurations
    |--------------------------------------------------------------------------
    */
    'commands'            => [
        /*
        |--------------------------------------------------------------------------
        | Configuration for the db:export-csv command
        |--------------------------------------------------------------------------
        */
        'export_csv' => [
            /*
            |--------------------------------------------------------------------------
            | Add column names
            |--------------------------------------------------------------------------
            |
            | Whether or not the csv files should contain the column names in the
            | first row.
            |
            */
            'with_headers' => true,

            /*
            |--------------------------------------------------------------------------
            | Tables to ignore
            |--------------------------------------------------------------------------
            |
            | The tables that should be ignored when creating csv files.
            |
            */
            'except'       => [
                'migrations',
                'password_resets',
            ],

            /*
            |--------------------------------------------------------------------------
            | Export chunk size
            |--------------------------------------------------------------------------
            |
            | Set the number of items to be written into the csv file at a time.
            | This will decrease the php memory needed to write the files.
            |
            | NOTE: If you have a large amount of data a small chunk size can cause
            |       the export to fail e.g. out of memory exception.
            |
            */
            'export_chunk_size' => 100,
        ],
    ],
];
