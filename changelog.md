# Changelog

All notable changes to `LaravelCsvFileSeeder` will be documented in this file.

## Version 1.0

### Added
- Everything

## Version 1.0.1
- When exporting data from the database null values will now be converted to 'NULL' strings instead of an empty string.

## Version 1.0.2
- Added export chunk size to avoid memory issues while exporting a large amount of data.
- Added progress bars to the export and seed command for long running tasks.

## Version 1.0.3
- Refactored the data export to use laravel's chunk method with a sort on the first column of the table. The prior version will fail on tables without an 'id' column because of the usage of laravel's chunkById method!