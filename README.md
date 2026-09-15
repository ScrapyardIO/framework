# scrapyard-io/framework (0.8)

[![Tests](https://github.com/scrapyard-io/framework/actions/workflows/tests.yml/badge.svg)](https://github.com/scrapyard-io/framework/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/scrapyard-io/framework.svg)](https://packagist.org/packages/scrapyard-io/framework)
[![Total Downloads](https://img.shields.io/packagist/dt/scrapyard-io/framework.svg)](https://packagist.org/packages/scrapyard-io/framework)
[![License](https://img.shields.io/packagist/l/scrapyard-io/framework.svg)](LICENSE)
[![Docs](https://img.shields.io/badge/docs-ScrapyardIO-0ea5e9?logo=readthedocs&logoColor=white)](https://scrapyard-io.projectsaturnstudios.com/ecosystem/scrapyard-io/framework/0.8.x/overview)

GPIO protocol adapters + **Circuits** registry for ScrapyardIO framework **0.8**, on `venusian/framework`.

## Install

```bash
composer require scrapyard-io/framework:^0.8.0
php workshop vendor:publish --tag=gpio-config
```

Discovery registers `ScrapyardIOServiceProvider` and MagicAliases `GPIO` / `Circuit`.

## Basics

**Protocol connection**

```php
use GeneralPurposeIO\Core\MagicAliases\GPIO;
use GeneralPurposeIO\I2C\I2C;

$slave = I2C::adapter('posix')->device(1)->bus()->slave(0x3C);
// or via GPIO::protocol('i2c') when configuring adapters from gpio.php
```

**Circuit bootstrap**

```php
use GeneralPurposeIO\Core\MagicAliases\Circuit;

Circuit::addCircuit('aht20', \DeptOfScrapyardRobotics\Sensors\AHTx0\AHT20\AHT20::class);

$sensor = Circuit::ic('aht20')
    ->protocol('i2c')
    ->driver('posix')
    ->device(1)
    ->slave(0x38)
    ->make();

// or a named profile from config/circuits.php
$sensor = Circuit::profile('climate_lab');
```

Scaffold profiles from `#[Pinout]`:

```bash
php workshop circuit:make-profile
```

Workshop `about` lists **GPIO** adapter availability and **Integrated Circuits** catalog options (not profiles).

## Dock

0.7 works. Blocking IO through Digital / I2C / SPI / UART / PWM stays as
is. 0.8 adds one `gpio` resource on the `venusian/framework` IOPool dock
so ICs can opt into next-tick delivery — a call to `watch`, `receive`, or
`defer` opts an IC in per call; an IC that does not call one stays
blocking.

```php
// blocking — unchanged from 0.7
$slave = I2C::adapter('posix')->device(1)->bus()->slave(0x38);
$bytes = $slave->read(7);

// dock — new, opt-in per IC
$gpio = IOPool::gpio();
$gpio->watch($button, rising: true);                       // DigitalEdgeOccurrence next pump
$gpio->receive($gps_port);                                 // UARTBytesOccurrence next pump
$gpio->defer('aht20.measure', fn () => $slave->read(7))    // TransferCompletion next pump
    ->onSuccess(fn (TransferCompletion $c) => $climate->ingest($c->result));

// loop — $app->tick() / $app->events() are Surface's LiveApplication API;
// a sketch without Surface pumps the dock with IOPool::pump() and reads
// mail back with IOPool::drain() instead.
$app->tick(16);
foreach ($app->events() as $mail) { ... }
```

## Where this package sits

`ext-posi` / `ext-ftdi` (1:1 syscalls) → `microscrap/*` (libgpiod / libmpsse / spidev / termios in PHP) → **`scrapyard-io/framework`** (protocol managers, buses, Circuits, the `gpio` dock resource) → `dept-of-scrapyard-robotics/*` (chip drivers) → `venusian/surface` EmbeddedPanels.

## Component splits

This umbrella replaces the nine `src/GeneralPurposeIO/*` subtree packages at `self.version`:

| Composer | Component |
|---|---|
| `gpio/analog` | `src/GeneralPurposeIO/Analog` |
| `gpio/circuits` | `src/GeneralPurposeIO/Circuits` |
| `gpio/common` | `src/GeneralPurposeIO/Common` |
| `gpio/contracts` | `src/GeneralPurposeIO/Contracts` |
| `gpio/digital` | `src/GeneralPurposeIO/Digital` |
| `gpio/i2c` | `src/GeneralPurposeIO/I2C` |
| `gpio/pwm` | `src/GeneralPurposeIO/PWM` |
| `gpio/spi` | `src/GeneralPurposeIO/SPI` |
| `gpio/uart` | `src/GeneralPurposeIO/UART` |

`Core` (`src/GeneralPurposeIO/Core`) is not split — the aggregate provider, the `GPIO` / `Circuit` aliases, and the `gpio` dock resource ship only with the umbrella.

Prefer requiring **`scrapyard-io/framework`**. Each `gpio/*` package installs alone from its own `composer.json` for anyone who wants one protocol without the rest.

## Carriers

| Path | Packages |
|---|---|
| Native | `microscrap/posix` + `gpio` / `i2c` / `spi` / `uart` + `ext-posi` |
| USB | `microscrap/ftdi` → `mpsse` + `ext-ftdi` |

All `^0.8.0`. Every microscrap package is `suggest`, never `require` — a posix-only install must not drag `ext-ftdi`.

## Tests

```bash
composer update
vendor/bin/pest
```

## License

MIT
