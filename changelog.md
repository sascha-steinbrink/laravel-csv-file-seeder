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