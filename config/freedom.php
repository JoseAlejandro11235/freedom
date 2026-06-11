<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Filament back-office URL path (no leading slash)
    |--------------------------------------------------------------------------
    */

    'admin_path' => env('FILAMENT_ADMIN_PATH', 'admin-backoffice-free-dom'),

    /*
    |--------------------------------------------------------------------------
    | Brand logo (MinIO / S3 object key)
    |--------------------------------------------------------------------------
    */

    'logo_path' => env('BRAND_LOGO_PATH', 'freedom.png'),

    'logo_url' => env('BRAND_LOGO_URL'),

    /*
    |--------------------------------------------------------------------------
    | Favicon (optional MinIO key; defaults to public/favicon.svg)
    |--------------------------------------------------------------------------
    */

    'favicon_path' => env('BRAND_FAVICON_PATH'),

    'favicon_url' => env('BRAND_FAVICON_URL'),

    /*
    |--------------------------------------------------------------------------
    | Catalog media disk (products, categories — stored paths in DB)
    |--------------------------------------------------------------------------
    */

    'catalog_disk' => env('FILESYSTEM_DISK', 'local'),

];
