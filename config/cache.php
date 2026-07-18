<?php

use Fabricate\NutsAndBolts\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache store that will be used by the
    | framework. This connection is utilized if another isn't explicitly
    | specified when running a cache operation inside the application.
    |
    */

    'default' => env('CACHE_STORE', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Cache Limiter Store
    |--------------------------------------------------------------------------
    |
    | This option controls which cache store the RateLimiter uses when no
    | store is explicitly provided. Defaults to the array store for local
    | CLI workloads without depending on Redis availability.
    |
    */

    'limiter' => env('CACHE_LIMITER_STORE', 'array'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | You may define all of the cache "stores" for your application as well
    | as their drivers. Supported for ScrapyardIO / Fabricate (non-server):
    | "array", "file", "redis", "storage", "failover", "null"
    |
    | Deferred: database, memcached, dynamodb, session, apc, octane
    |
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

        'storage' => [
            'driver' => 'storage',
            'disk' => env('CACHE_STORAGE_DISK'),
            'path' => env('CACHE_STORAGE_PATH', 'framework/cache/data'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

        'failover' => [
            'driver' => 'failover',
            'stores' => [
                'redis',
                'file',
                'array',
            ],
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing Redis (and similar) cache stores, there might be other
    | applications using the same cache. Prefix keys to avoid collisions.
    |
    */

    'prefix' => env('CACHE_PREFIX', Str::slug((string) env('APP_NAME', 'scrapyard-io')).'-cache-'),

];
