# Japanese Postal Codes

Laravel package for Japanese prefectures and postal codes. The data is imported from the official Japan Post service.

## Requirements

- PHP 7.4 or higher
- Laravel 7.0 or higher

## Installation

```bash
composer require ethantechnology/laravel-jp-postal-codes
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
- Change the table names for prefectures, cities, and postal codes
- Modify the URL for downloading postal code data
- Adjust import settings like chunk size

### Customizing Migrations

Since we no longer automatically load migrations, you need to publish them first:

```bash
php artisan vendor:publish --tag=jp-postal-codes-migrations
```

After publishing, you can find and modify the migration files in your `database/migrations` directory. You should customize them before running migrations if you:

1. Changed table names in the config
2. Need to adjust table structures (add or modify columns)
3. Want to change indexes or foreign key constraints

> **Important**: Make sure your migration table names match those in the config file. If you change table names in the config, update the corresponding migration files before running migrations.

## Migration

After installing and configuring, run:

```bash
php artisan migrate
```

This will create the following tables:
- `prefectures`: Contains all Japanese prefectures (or your custom table name if configured)
- `cities`: Contains all Japanese cities/municipalities (or your custom table name if configured)
- `postal_codes`: Contains Japanese postal codes with their corresponding prefectures and cities (or your custom table name if configured)

## Updating Postal Code Data

To download and update all data from the official Japan Post service:

```bash
php artisan jp-postal-codes:update
```

This command will:
1. Download the latest postal code data from Japan Post
2. Clean all existing data in the tables
3. Import the postal codes
4. Automatically extract and create prefectures and cities from the postal code data

Note: This process downloads data from the official Japan Post service and might take some time to complete. The data is provided in Shift-JIS encoding and will be automatically converted to UTF-8 during import.

## Usage

```php
use Eta\JpPostalCodes\Models\Prefecture;
use Eta\JpPostalCodes\Models\City;
use Eta\JpPostalCodes\Models\PostalCode;

// Get all prefectures
$prefectures = Prefecture::all();

// Find prefecture by ID
$tokyo = Prefecture::find(13);

// Get all cities in a prefecture
$tokyoCities = $tokyo->cities;

// Find city by ID
$city = City::find('13104');

// Get the prefecture for a city
$prefecture = $city->prefecture;

// Find location by postal code
$locations = PostalCode::where('postal_code', '1600022')->get();
// Or using the scope
$locations = PostalCode::postal('1600022')->get();

// Get the city and prefecture for a postal code
$location = PostalCode::postal('1600022')->first();
$city = $location->city;
$prefecture = $location->prefecture;
```

## Custom Table Names

After publishing the config file, you can modify the table names:

```php
// config/jp-postal-codes.php
return [
    'tables' => [
        'prefectures' => 'custom_prefectures',
        'cities' => 'custom_cities',
        'postal_codes' => 'custom_postal_codes',
    ],
    // ...
];
```

Make sure to update your migration files to match these table names before running migrations.

## License

MIT 