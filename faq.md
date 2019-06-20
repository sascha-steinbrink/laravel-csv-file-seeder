# Frequently Asked Questions

- [How to avoid 'Integrity constraint violation' errors while seeding](#how-to-avoid-integrity-constraint-violation-errors-while-seeding)
- [Some of my tables are empty after seeding](#some-of-my-tables-are-empty-after-seeding)

## How to avoid 'Integrity constraint violation' errors while truncating

To avoid 'Integrity constraint violation' errors while truncating you can disable
the foreign key checks:

* globally for all database seeding/export tasks in the [config/laravel-csv-file-seeder.php configuration file](config/laravel-csv-file-seeder.php)
* or by providing the ```-k false``` / ```--foreign-key-checks=false``` option

> To disable the foreign key checks globally in the config file you have to publish the configuration file before.

## Some of my tables are empty after seeding

There are multiple reasons why this can happen:

* Is the name of the csv file equal to the table name?
* Does the csv file has the right delimiter?
* Is the csv file in the right directory?
* Run the seed command again in debug mode (```php artisan csv:seed -vvv```) and check the output