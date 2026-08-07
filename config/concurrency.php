<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Concurrency Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "process", "fork", "sync", "fiber", "pokio"
    |
    | Use `sync` in tests or on constrained edge hardware. Production defaults
    | to isolated Workshop child processes via the `process` driver.
    | Prefer `fiber` for sketch-safe cooperative AsyncNode work; `pokio`
    | requires the suggested nunomaduro/pokio package (process fork fan-out).
    |
    */

    'default' => env('CONCURRENCY_DRIVER', 'process'),

];
