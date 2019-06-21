# LaravelCsvFileSeeder

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]

This package provides the ability to import or export your database to csv or zip files.

* [Installation](#installation)
* [Usage](#usage)
  * [Available seed options](#available-seed-options)
  * [Available export options](#available-export-options)
  * [Using a different path](#using-a-different-path)
  * [Using a different delimiter](#using-a-different-delimiter)
  * [Using an archive](#using-an-archive)
  * [Only specific files](#only-spcific-files)  
  * [Using an encrypted archive](#using-an-encrypted-archive)
  * [Using a connection other than the default](#using-a-connection-other-than-the-default)
* [FAQ](#faq)

This is where your description should go. Take a look at [contributing.md](contributing.md) to see a to do list.

## Installation

This package can be used in Laravel **5.5** or higher!

Via Composer

``` bash
composer require sascha-steinbrink/laravel-csv-file-seeder
```

> If you want to use zipped files make sure ```zip``` and ```unzip``` is installed on your system!

You can publish the configuration file with the following command:

``` bash
php artisan vendor:publish --provider="SaschaSteinbrink\LaravelCsvFileSeeder\LaravelCsvFileSeederServiceProvider" --tag="config"
```

The ```config/laravel-csv-file-seeder.php``` configuration file contains:

``` php
return [

    /*
    |--------------------------------------------------------------------------
    | Database connection
    |--------------------------------------------------------------------------
    |
    | The database connection to use for seeding.
    |
    */
    'connection' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Data path
    |--------------------------------------------------------------------------
    |
    | The folder the csv files are located in. It defaults to database/data.
    |
    */
    'data_path' => database_path('data'),

    /*
    |--------------------------------------------------------------------------
    | Delimiter character
    |--------------------------------------------------------------------------
    |
    | The csv delimiter to use for parsing csv fields.
    |
    */
    'delimiter' => ',',

    /*
    |--------------------------------------------------------------------------
    | Enclosure character
    |--------------------------------------------------------------------------
    |
    | The csv enclosure to use for parsing csv fields.
    |
    */
    'enclosure' => '"',

    /*
    |--------------------------------------------------------------------------
    | Escape character
    |--------------------------------------------------------------------------
    |
    | The csv escape to use for parsing csv fields.
    |
    */
    'escape' => '\\',

    /*
    |--------------------------------------------------------------------------
    | Trim csv values
    |--------------------------------------------------------------------------
    |
    | Indicates if any leading or trailing white space should be trimmed
    | from the csv fields.
    |
    */
    'trim_values' => true,

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
    'insert_chunk_size' => 50,

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
    'truncate' => true,

    /*
    |--------------------------------------------------------------------------
    | Foreign key checks
    |--------------------------------------------------------------------------
    |
    | Enable or disable foreign key checks while truncating.
    |
    */
    'foreign_key_checks' => true,

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
    'files'     => [],

    /*
    |--------------------------------------------------------------------------
    | Zipped export
    |--------------------------------------------------------------------------
    |
    | Whether or not export an zip archive containing the csv files.
    |
    */
    'zipped' => false,

    /*
    |--------------------------------------------------------------------------
    | Archive name
    |--------------------------------------------------------------------------
    |
    | The archive name to use for saving.
    |
    */
    'archive_name' => 'db-csv-export.zip',

    /*
    |--------------------------------------------------------------------------
    | Encrypted zip archive
    |--------------------------------------------------------------------------
    |
    | Whether or not the exported zip archive should be password protected.
    |
    */
    'encrypted' => false,

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
    'commands' => [
        /*
        |--------------------------------------------------------------------------
        | Configuration for the csv:export command
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
            'except' => [
                'migrations',
                'password_resets'
            ]
        ]
    ]
];
```

## Usage

You can seed or export your database through the console using the following artisan commands:

``` bash
php artisan csv:export
```

``` bash
php artisan csv:seed
```

To seed your database using laravel's ```php artisan db:seed``` command you have to register 
the ```LaravelCsvFileSeeder::class``` in the ```database\seeds\DatabaseSeeder::class``` class. 

``` php
use SaschaSteinbrink\LaravelCsvFileSeeder\LaravelCsvFileSeeder;
// ...
 public function run()
{
    // $this->call(UsersTableSeeder::class);
    $this->call(LaravelCsvFileSeeder::class);
}
```

> The main difference between those two methods is the flexibility of changing the
> default values of the configuration file through command options which is only possible with 
> the ```php artisan csv:seed``` command.

Without any options given or by using the ``` php artisan db:seed``` command all available commands will use the values from 
the [config/laravel-csv-file-seeder.php configuration file](config/laravel-csv-file-seeder.php). 

The default data path in the config file points to *database/data*. Therefore a call to ```php artisan db:seed``` or 
```php artisan csv:seed``` would seed all files from the *database/data* directory with an *.csv* extension into the 
database. 

> The csv file names must match the corresponding table names! All csv files where the name does not 
> match any table in the database will be ignored.

> You can debug the output of the commands to see which files were ignored (and other information) by increasing the 
> verbosity level (-v/-vv/-vvv)!

> If you are getting 'Integrity constraint violation' errors while seeding try to disable the foreign key checks either by specifying 
> the ```-k false``` / ```--foreign-key-checks=false``` option or by disable them globally in the [config/laravel-csv-file-seeder.php configuration file](config/laravel-csv-file-seeder.php) 

The export command ```php artisan csv:export``` will export all tables (except the ones specified in the config
file which are by default *migrations* and *password_resets*) into *database/data* directory.

To be able to change any of the configuration values you have to publish the configuration file. See the [installation 
instructions](#installation) to do so.

### Available seed options

> Note: This options can only be assigned to the ```php artisan csv:seed``` command! To change any option for the
> ```php artisan db:seed``` command you have to change the values in the configuration file!

| Option               |    | Default             | Description                                                |
|----------------------|----|---------------------|------------------------------------------------------------|
| --data-path          | -p | database/data       | The folder the csv files are located in                    |
| --files              | -i | []                  | The files that should be seeded                            |
| --delimiter          | -d | ','                 | The delimiter character to use for parsing csv fields      |
| --enclosure          | -l | '"'                 | The enclosure character to use for parsing csv fields      |
| --escape             | -c | '\\'                | The escape character to use for parsing csv fields         |
| --trim-values        | -m | true                | Should trim lead-/trailing white spaces from csv fields    |
| --insert-chunk-size  | -s | 50                  | The number of rows to read before an insert query is fired |
| --truncate           | -t | true                | Truncate the desired tables before seeding                 |
| --foreign-key-checks | -k | true                | Enable/disable foreign key checks while truncating         |
| --zipped             | -z | false               | Import data is an archive (zip) file                       |
| --archive-name       | -a | "db-csv-export.zip" | The archive name to import                                 |
| --encrypted          | -e | false               | The import archive is encrypted                            |
| --connection         |    | default             | The database connection to seed                            |
| --force              |    |                     | Force the operation to run when in production              |

> The ```--connection``` defaults to the default database connection (```config('database.default')```)


### Available export options

| Option         |    | Default                             | Description                                           |
|----------------|----|-------------------------------------|-------------------------------------------------------|
| --data-path    | -p | database/data                       | The folder the csv files should be stored             |
| --except       | -x | "migrations,password_resets"        | The tables that should be ignored                     |
| --with-headers | -w | true                                | Should include column names                           |
| --delimiter    | -d | ','                                 | The delimiter character to use for parsing csv fields |
| --enclosure    | -l | '"'                                 | The enclosure character to use for parsing csv fields |
| --escape       | -c | '\\'                                | The escape character to use for parsing csv fields    |
| --zipped       | -z | false                               | Export data as an archive (zip) file                  |
| --archive-name | -a | "db-csv-export.zip"                 | The archive name to use for saving                    |
| --encrypted    | -e | false                               | Encrypt the archive                                   |
| --connection   |    | default                             | The database connection to export                     |

> The ```--connection``` defaults to the default database connection (```config('database.default')```)

### Using a different path

To seed all csv files located in */my/path*:

``` bash
php artisan csv:seed -p "/my/path"
```

To export all tables (except the ones specified in the config file) as csv files into */my/path*:

``` bash
php artisan csv:export -p "/my/path"
```

### Using a different delimiter

To seed all csv files located in */my/path* using a semicolon as delimiter:

``` bash
php artisan csv:seed -p "/my/path" -d ";"
```

To export all tables (except the ones specified in the config file) as csv files into */my/path*
using a semicolon as delimiter:

``` bash
php artisan csv:export -p "/my/path" -d ";"
```

### Using an archive file

> The zip option is **not available on windows**!

To seed the export.zip archive located in */my/path*:

``` bash
php artisan csv:seed -z true -p "/my/path" -a "export"
```

To export all tables (except the ones specified in the config file) into *database/data/**dbExport.zip***:

``` bash
php artisan csv:export -z true -a "dbExport"
```

### Only specific files

To import only the *users.csv* file located in *database/data*:

``` bash
php artisan csv:seed -i "users"
```

To import only the *users.csv* file located in */my/other/path*:

``` bash
php artisan csv:seed -i "users" -p "/my/other/path"
```

To export all tables except the *users* table and the *migrations* table to the *database/data* directory:

``` bash
php artisan csv:export -x "migrations,users"
```


### Using an encrypted archive

> The zip option is **not available on windows**!

When providing the ```-e true``` or ```--encrypt=true``` option in combination with the
```-z true``` or ```--zipped=true``` option a password prompt will be shown, where you can
specify the password to use for encryption.

> The ```-e true``` or ```--encrypt=true``` will be ignored when zipping is **not** activated.
> (Either by setting the option ```-z true``` / ```--zipped=true``` or by enabling the *zipped* option
> in the ```config/laravel-csv-file-seeder.php``` configuration file.)


To seed the encrypted db-csv-export.zip archive located in */my/path*:

``` bash
php artisan csv:seed -z true -p "/my/path" -e
```

To export all tables into an encrypted */my/path/**encrypted.zip*** archive:

``` bash
php artisan csv:export -z true -x "" -a "encrypted" -e
```

**NOTE**: 
> If you want to specify a default encryption password for your project you can add
> it to your env file using the ```CSV_SEEDER_ENCRYPTION_PASSWORD``` key.


### Using a connection other than the default

To seed all csv files located in */my/path* using the database bound to *my-other-db* connection:

``` bash
php artisan csv:seed -p "/my/path" --connection="my-other-db"
```

To export all tables as csv files into */my/path* using the database bound to *my-other-db* connection:

``` bash
php artisan csv:export -p "/my/path" -x "" --connection="my-other-db"
```

> The ```-x ""``` is set to also include the excepted ones specified in the config file which are by default *migrations* and *password_resets*

## FAQ

Please see the [FAQ](faq.md) for more information.

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing

``` bash
composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email author email instead of using the issue tracker.

## Credits

- [Sascha Steinbrink][link-author]
<!--- [All Contributors][link-contributors]-->

## License

license. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/sascha-steinbrink/laravel-csv-file-seeder.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/sascha-steinbrink/laravel-csv-file-seeder.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/sascha-steinbrink/laravel-csv-file-seeder/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/192954833/shield

[link-packagist]: https://packagist.org/packages/sascha-steinbrink/laravel-csv-file-seeder
[link-downloads]: https://packagist.org/packages/sascha-steinbrink/laravel-csv-file-seeder
[link-travis]: https://travis-ci.org/sascha-steinbrink/laravel-csv-file-seeder
[link-styleci]: https://styleci.io/repos/192954833
[link-author]: https://github.com/sascha-steinbrink
[link-contributors]: ../../contributors
