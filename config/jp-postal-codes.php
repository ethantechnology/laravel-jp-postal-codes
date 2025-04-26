<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Japanese Postal Codes Database Tables
    |--------------------------------------------------------------------------
    |
    | You can customize the table names used by this package.
    | Make sure to run your migrations after changing these values.
    |
    */
    
    'tables' => [
        'prefectures' => 'jp_prefectures',
        'cities' => 'jp_cities',
        'postal_codes' => 'jp_postal_codes',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Data Source Settings
    |--------------------------------------------------------------------------
    |
    | Configure the data source for postal codes.
    |
    */
    
    'postal_code_url' => 'https://www.post.japanpost.jp/zipcode/dl/kogaki/zip/ken_all.zip',
    
    /*
    |--------------------------------------------------------------------------
    | Import Settings
    |--------------------------------------------------------------------------
    |
    | Settings for the import process.
    |
    */
    
    'import' => [
        'chunk_size' => 1000, // Number of records to insert at once
    ],
]; 