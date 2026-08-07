<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Additional Sketch Classes
    |--------------------------------------------------------------------------
    |
    | List fully-qualified Sketch class names that should be registered when
    | the application boots. Each class must declare the #[Sketch('name')]
    | attribute. Conventional app/Runner/Sketches discovery does not require this.
    |
    */

    'load' => [
        // \App\Other\MyAttributedSketch::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Runner Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware runs through fabricate/pipeline around each sketch invocation.
    | Destination is the Flow-hosted SketchRunner. Default stack is empty.
    |
    */

    'middleware' => [
        // \App\Runner\Middleware\Example::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | AsyncNode Concurrency Driver
    |--------------------------------------------------------------------------
    |
    | Default driver for AsyncNode / AsyncFlow fan-out. Prefer `fiber` for
    | sketch-safe cooperative work. Use `pokio` when nunomaduro/pokio is installed.
    |
    */

    'concurrency' => env('SKETCHES_CONCURRENCY_DRIVER', 'fiber'),

];
