# Japanese Postal Codes

Laravel package for Japanese prefectures and postal codes.

## Requirements

- PHP 7.4 or higher
- Laravel 7.0 or higher

## Installation

```bash
composer require laravel-jp-postal-codes
```

### Service Provider Registration

#### For Laravel 5.5+

If you're using Laravel 5.5 or higher, the package will automatically register its service provider thanks to Laravel's package discovery feature.

#### For Laravel 5.4 and below

If you're using Laravel 5.4 or below, you need to manually register the service provider. Add the following line to the `providers` array in your `config/app.php` file:

```php
Eta\JpPostalCodes\JpPostalCodesServiceProvider::class,
```

## Configuration

To customize the table names and other settings, publish the configuration file:

```bash
php artisan vendor:publish --tag=jp-postal-codes-config
```

This will create a `config/jp-postal-codes.php` file where you can:
- Change the table names for prefectures and postal codes
- Modify the URL for downloading postal code data
- Adjust import settings like chunk size

> **Important**: If you change table names in the config, make sure to update the corresponding migration files in your `database/migrations` directory before running migrations.

## Migration

The package automatically publishes its migrations when installed. After installing and configuring, run:

```bash
php artisan migrate
```

This will create the following tables:
- `jp_prefectures`: Contains all Japanese prefectures (or your custom table name if configured)
- `jp_postal_codes`: Contains Japanese postal codes with their corresponding prefectures (or your custom table name if configured)

## Import Data

You can import all prefectures and postal codes data using:

```bash
php artisan jp-postal-codes:import
```

To import only prefectures:

```bash
php artisan jp-postal-codes:import --only-prefectures
```

To import only postal codes (requires internet connection):

```bash
php artisan jp-postal-codes:import --only-postal-codes
```

Note: Postal code import downloads data from a public source and might take some time to complete.

## Usage

```php
use Eta\JpPostalCodes\Models\Prefecture;
use Eta\JpPostalCodes\Models\PostalCode;

// Get all prefectures
$prefectures = Prefecture::all();

// Find prefecture by ID
$tokyo = Prefecture::find(13);

// Find location by postal code
$locations = PostalCode::where('postal_code', '1000001')->get();
// Or using the scope
$locations = PostalCode::postal('1000001')->get();
```

## Custom Table Names

After publishing the config file, you can modify the table names:

```php
// config/jp-postal-codes.php
return [
    'tables' => [
        'prefectures' => 'custom_prefectures',
        'postal_codes' => 'custom_postal_codes',
    ],
    // ...
];
```

Make sure to update your migration files to match these table names before running migrations.

## License

MIT 