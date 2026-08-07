<?php

return [
    'driver' => 'neo4j',
    'host' => env('NEO4J_HOST', 'localhost'),
    'port' => env('NEO4J_PORT', 7687),
    'database' => env('NEO4J_DATABASE', 'neo4j'),
    'username' => env('NEO4J_USERNAME', 'neo4j'),
    'password' => env('NEO4J_PASSWORD', ''),
    'scheme' => env('NEO4J_SCHEME', 'bolt'),
    'prefix' => env('NEO4J_PREFIX', ''),
    'options' => [
        'timeout' => 30,
    ],
];
