<?php

use Fabricate\NutsAndBolts\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | Supported public drivers for ScrapyardIO (local desktop / edge): "file"
    | and "redis". The "array" store exists for in-process tests and the
    | RateLimiter default — not for production workloads.
    |
    */

    'default' => env('CACHE_STORE', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Cache Limiter Store
    |--------------------------------------------------------------------------
    |
    | RateLimiter store when none is specified. Defaults to array so local CLI
    | work does not require Redis.
    |
    */

    'limiter' => env('CACHE_LIMITER_STORE', 'array'),

    /*
    |--------------------------------------------------------------------------
    | Schedule Cache Store
    |--------------------------------------------------------------------------
    |
    | The cache store used by the scheduler for mutexes and pause/interrupt
    | signals. When null, the default cache store is used.
    |
    */

    'schedule_store' => env('SCHEDULE_CACHE_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    */

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    */

    'prefix' => env('CACHE_PREFIX', Str::slug((string) env('APP_NAME', 'scrapyard-io')).'-cache-'),

];
