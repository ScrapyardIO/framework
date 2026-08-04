# ScrapyardIO Framework

[![Latest Version on Packagist](https://img.shields.io/packagist/v/scrapyard-io/framework.svg)](https://packagist.org/packages/scrapyard-io/framework)
[![Total Downloads](https://img.shields.io/packagist/dt/scrapyard-io/framework.svg)](https://packagist.org/packages/scrapyard-io/framework)
[![License](https://img.shields.io/packagist/l/scrapyard-io/framework.svg)](LICENSE.md)

ScrapyardIO is a PHP application framework for building programs that interact with sensors, displays, integrated circuits, and other physical hardware.

The framework supplies the application runtime: dependency injection, configuration, package discovery, console commands, lifecycle-managed sketches, hardware registries, rendering, and supporting services. Protocol adapters and device drivers are separate packages that applications can compose as needed.

Disclaimer - The framework was built LLM-assisted. It was not "vibe" coded. Any code that was generated was carefully prompted and reviewed by a human professional software engineer.
This package (when cloned) contains an .okf bundle which is what is used along with the AI-assistant to produce high-quality code when utilized.

## Requirements

- PHP 8.4, 8.5, or 8.6
- [Composer](https://getcomposer.org/)

## Installation

```bash
composer require scrapyard-io/framework
```

The framework package does not scaffold an application. Wire up the project with the files below, or start from the [`ScrapyardIO/scrapyard-io`](https://github.com/ScrapyardIO/scrapyard-io) skeleton.

### Minimal application bootstrap

Merge these entries into the project's `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    },
    "scripts": {
        "post-autoload-dump": [
            "Fabricate\\Core\\ComposerScripts::postAutoloadDump",
            "@php workshop package:discover --ansi"
        ]
    },
    "extra": {
        "scrapyard-io": {
            "dont-discover": []
        }
    }
}
```

Create these directories:

```text
app/Sketches/
bootstrap/cache/
config/
storage/framework/cache/data/
storage/logs/
```

Create `bootstrap/app.php`:

```php
<?php

use Fabricate\Core\Machine;

return Machine::configure(basePath: dirname(__DIR__))
    ->create();
```

Create `bootstrap/providers.php`:

```php
<?php

return [];
```

Create the application sketch base at `app/Sketches/Sketch.php`:

```php
<?php

namespace App\Sketches;

use Fabricate\Sketches\Sketch as FrameworkSketch;

abstract class Sketch extends FrameworkSketch
{
}
```

Create the `workshop` console entry point:

```php
#!/usr/bin/env php
<?php

use Fabricate\Core\Machine;
use Symfony\Component\Console\Input\ArgvInput;

require __DIR__.'/vendor/autoload.php';

/** @var Machine $app */
$app = require_once __DIR__.'/bootstrap/app.php';

exit($app->handleCommand(new ArgvInput));
```

Make Workshop executable and verify the install:

```bash
chmod +x workshop
composer dump-autoload
php workshop sketch:list
```

## Your First Sketch

A sketch is a foreground workload with a controlled lifecycle:

1. `boot()` prepares resources.
2. `loop()` performs one cooperative unit of work.
3. `shutdown()` releases resources, including when an exception escapes.

Generate one with Workshop:

```bash
php workshop make:sketch HelloHardware
```

Edit `app/Sketches/HelloHardware.php`:

```php
<?php

namespace App\Sketches;

use Fabricate\Contracts\Sketches\SketchLoopResult;

class HelloHardware extends Sketch
{
    protected string $description = 'Run one lifecycle-safe hardware tick.';

    public function boot(): void
    {
        $this->info('Preparing hardware...');
    }

    public function loop(): SketchLoopResult
    {
        $this->info('Hello from ScrapyardIO.');

        return SketchLoopResult::STOP;
    }

    public function shutdown(): void
    {
        $this->info('Hardware released.');
    }
}
```

Application sketches under `app/Sketches` are discovered automatically. Their class names become kebab-case command names:

```bash
php workshop sketch:list
php workshop sketch hello-hardware
```

Return `SketchLoopResult::CONTINUE` to schedule another tick or `SketchLoopResult::STOP` to finish normally. When `pcntl` is available, `SIGINT` and `SIGTERM` request a cooperative stop so `shutdown()` can run.

## Application Structure

A skeleton application uses this layout:

```text
app/
  Console/Commands/    Application commands
  Providers/           Application service providers
  Sketches/            Lifecycle-managed workloads
bootstrap/
  app.php               Machine construction
  providers.php         Application providers
config/                 Application and hardware configuration
storage/                Logs, caches, and generated state
workshop                Console entry point
```

`bootstrap/app.php` creates the application:

```php
<?php

use Fabricate\Core\Machine;

return Machine::configure(basePath: dirname(__DIR__))
    ->create();
```

The `Machine` is both the application runtime and service container. During startup it loads environment variables and configuration, discovers packages, registers and boots service providers, and dispatches the requested console command.

## Configuration

Configuration files are PHP arrays stored in `config/`. Read values with the `config()` helper:

```php
$name = config('machine.name');
$display = config('displays.main');
```

Environment-specific values belong in `.env` and can be referenced with `env()` from configuration files:

```php
// config/machine.php
return [
    'name' => env('APP_NAME', 'ScrapyardIO'),
];
```

Framework defaults are merged with application configuration. Common hardware definitions live in:

- `config/circuits.php` for integrated-circuit drivers and bus parameters
- `config/sensors.php` for sensor abstractions backed by circuits
- `config/displays.php` for windowed or embedded displays
- `config/gfx.php` for renderers and framebuffer behavior

Circuit parameters are documented by each `dept-of-scrapyard-robotics/*` package.

Inspect and cache configuration with Workshop:

```bash
php workshop config:show machine
php workshop config:cache
php workshop config:clear
```

## Hardware Stack

ScrapyardIO separates application concerns from transport and chip details:

```text
Sketch or command
    ↓
Fabricate sensor, display, and visual APIs
    ↓
Waveforms and Tubes abstractions
    ↓
Dept. of Scrapyard Robotics chip drivers
    ↓
GPIO Framework transports
    ↓
Native or USB bindings
```

Companion packages:

- `scrapyard-io/gpio-framework` — SPI, I²C, UART, digital I/O, PWM, and analog transports
- `scrapyard-io/waveforms` — higher-level sensor abstractions
- `scrapyard-io/tubes` — display-panel abstractions
- [`dept-of-scrapyard-robotics/*`](https://github.com/DeptOfScrapyardRobotics) — chip drivers
- [`microscrap/*`](https://github.com/microscrap) — native and USB I/O bindings

Packages advertise service providers through Composer metadata. ScrapyardIO discovers them after Composer autoload generation.

Rebuild the package manifest manually when needed:

```bash
php workshop package:discover
```

## Framework Capabilities

### Hardware and graphics

- Integrated-circuit, sensor, display, font, and framebuffer registries
- Windowed and embedded display pipelines
- Framebuffer strategies for full, paged, and dirty-region updates
- GFX rendering and font discovery
- A shared visual presentation API for windowed and embedded targets

### Application services

- Chassis dependency-injection container
- Service providers and package discovery
- Events, pipelines, and command bus
- Cache, filesystem, logging, queues, Redis, and process execution
- Symfony Console-based Workshop commands
- Environment loading and configuration caching

The package exposes these components under the `Fabricate\` namespace and replaces its individual `fabricate/*` component packages through Composer.

## Useful Workshop Commands

```bash
php workshop list
php workshop sketch:list
php workshop sketch <name>
php workshop make:sketch <name>
php workshop make:command <name>
php workshop make:font <name>
php workshop make:framebuffer <name>
php workshop package:discover
```

Run `php workshop help <command>` for command-specific arguments and options.

## Extending the Framework

Use a service provider to register application services or hardware implementations:

```php
<?php

namespace App\Providers;

use Fabricate\NutsAndBolts\ServiceProvider;

class ProgramServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind services into $this->app.
    }

    public function boot(): void
    {
        // Register drivers after all services are available.
    }
}
```

Add application providers to `bootstrap/providers.php`. Reusable Composer packages should publish a provider through their package metadata so package discovery can load it automatically.

## Testing

From a standalone framework checkout:

```bash
composer install
vendor/bin/pest
```

From the ScrapyardIO monorepo root:

```bash
vendor/bin/pest scrapyard-io/framework/tests
```

## Contributing

Issues and source code: [github.com/ScrapyardIO/framework](https://github.com/ScrapyardIO/framework).

## License

ScrapyardIO Framework is open-source software licensed under the [MIT License](LICENSE.md).
