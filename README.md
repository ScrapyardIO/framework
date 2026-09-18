# scrapyard-io/framework

[![Tests](https://github.com/scrapyard-io/framework/actions/workflows/tests.yml/badge.svg)](https://github.com/scrapyard-io/framework/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/scrapyard-io/framework.svg)](https://packagist.org/packages/scrapyard-io/framework)
[![License](https://img.shields.io/packagist/l/scrapyard-io/framework.svg)](LICENSE)
[![Docs](https://img.shields.io/badge/docs-ScrapyardIO-0ea5e9?logo=readthedocs&logoColor=white)](https://scrapyard-io.projectsaturnstudios.com/ecosystem/scrapyard-io/framework/0.8.x/overview)

Talk to hardware from a Venusian app: I2C, SPI, UART, digital pins and PWM behind one API, whether the bus is a Raspberry Pi's own or an FTDI USB board.

`scrapyard-io/framework` gives each protocol a connection manager and a MagicAlias, and defines the transports chip drivers are written against. Adapter packages plug the actual hardware in. A `gpio` resource on the Venusian IOPool dock lets drivers watch pins, receive serial bytes and run bus work on the app's tick instead of blocking.

```
ext-posi / ext-ftdi            1:1 system and libftdi calls
  → microscrap/*               libgpiod, i2c-dev, spidev, termios, libmpsse in PHP
    → microscrap/scrapyard-*   adapters: the `native` and `usb` drivers
      → scrapyard-io/framework protocol managers, transports, the gpio dock   ← this package
        → dept-of-scrapyard-robotics/*   chip drivers
```

## Requirements

- PHP 8.4 or newer
- A Venusian application on `venusian/framework` 0.8
- At least one adapter:

| Adapter | Driver name | Protocols | Hardware |
|---|---|---|---|
| `microscrap/scrapyard-linux` | `native` | I2C, SPI, UART, digital, PWM | Linux `i2c-dev`, `spidev`, serial ports, `libgpiod`, sysfs PWM; needs `ext-posi` |
| `microscrap/scrapyard-usb` | `usb` | I2C, SPI, UART, digital | FTDI boards such as the FT232H, over MPSSE; needs `ext-ftdi` |

Without an adapter, every protocol uses the built-in `none` driver, which throws as soon as you try to connect.

## Installation

```bash
composer require scrapyard-io/framework 
```

Both service providers are discovered automatically. The framework registers the protocol managers and the MagicAliases `I2C`, `SPI`, `UART`, `DigitalIO`, `PWM` and `GPIO`. Each adapter registers its driver name on every manager it supports.

To choose default drivers, publish the config:

```bash
php computer vendor:publish --tag=gpio-config
```

```php
// config/gpio.php
return [
    'protocols' => [
        'i2c' => ['default' => 'native'],
        'spi' => ['default' => 'native'],
        'uart' => ['default' => 'native'],
        'digital-in' => ['default' => 'native'],   // the DigitalIO default
        'digital-out' => ['default' => 'native'],
        'pwm' => ['default' => 'native'],
    ],
    'io_pools' => [
        'enabled' => true,          // register the gpio dock resource
        'defer_per_tick' => null,   // cap deferred jobs per tick; null runs them all
    ],
];
```

`I2C::driver()` with no name uses the default. Naming the driver, as the examples below do, works whatever the defaults are.

## Quick start

Read a register from a device at `0x21` on a Raspberry Pi's `i2c-1`:

```php
use GeneralPurposeIO\I2C\I2C;

$device = I2C::driver('native')
    ->connectTo(1)       // open /dev/i2c-1
    ->register()         // keep the connection on the driver
    ->device(1, 0x21);   // a transport for one address on it

$device->probe();                           // true when something answers
$bytes = $device->writeRead([0xFC], 1);     // select register 0xFC, read one byte
```

## Connecting

Every protocol follows the same four steps:

1. `driver($name)` picks an adapter.
2. `connectTo($device)` starts a connection. For SPI and UART, set the bus options here.
3. `register()` opens the connection and stores it on the driver. The driver hands out transports from it until the app ends.
4. `device(...)` returns a transport for one address, chip select, port, pin or channel. It returns `null` if that device was never registered.

Connecting the same device twice throws. Register a bus once, then call `device()` as often as you need.

### I2C

```php
use GeneralPurposeIO\I2C\I2C;

$bus = I2C::driver('native')->connectTo(1)->register();
$display = $bus->device(1, 0x3C);
$fan = $bus->device(1, 0x21);

$ft232h = I2C::driver('usb')->connectTo('ft232h')->register()->device('ft232h', 0x53);
```

Transports on the same bus share one connection. Each call addresses its own device.

### SPI

```php
use GeneralPurposeIO\SPI\SPI;

$panel = SPI::driver('native')
    ->connectTo(0)              // /dev/spidev0.*
    ->mode(0)                   // SPIMode case or 0–3
    ->speed(8_000_000)          // Hz
    ->register()
    ->device(0, 0);             // chip select 0
```

`endianness()` and `chipSelect()` are also available. On the `usb` driver, set the bus speed with `clockRate()`, which takes an `MPSSEClockRate`, because that factory ignores `speed()`:

```php
use Microscrap\Bindings\MPSSE\Enums\MPSSEClockRate;

$panel = SPI::driver('usb')->connectTo('ft232h')->mode(0)->clockRate(MPSSEClockRate::TEN_MHZ)->register()->device('ft232h', 0);
```

### UART

```php
use GeneralPurposeIO\Contracts\UART\Parity;
use GeneralPurposeIO\UART\UART;

$gps = UART::driver('native')
    ->connectTo('/dev/ttyAMA0')
    ->baud(9600)
    ->parity(Parity::NONE)
    ->register()
    ->device('/dev/ttyAMA0');
```

`stopBits()`, `dataBits()` and `flowControl()` take their enums or the matching integers. The defaults are 9600 baud, 8 data bits, no parity, 1 stop bit and no flow control.

### Digital pins

```php
use GeneralPurposeIO\Contracts\Digital\LineBias;
use GeneralPurposeIO\Digital\DigitalIO;

$pins = DigitalIO::driver('native')->connectTo(0)->register();   // gpiochip0
$led = $pins->output(0, 17);
$button = $pins->input(0, 27, LineBias::PULL_UP);

$led->high();
$pressed = ! $button->read();
```

`input()` also takes `active_low`, which inverts the level the pin reports.

On an FTDI board, the I2C or SPI connection already registers the device, so its GPIOL pins are available straight away:

```php
$dc = DigitalIO::driver('usb')->output('ft232h', 1);
```

### PWM

```php
use GeneralPurposeIO\PWM\PWM;

$servo = PWM::driver('native')->connectTo(0)->register()->device(0, 0);   // pwmchip0, channel 0

$servo->setPeriod(20_000_000);      // nanoseconds
$servo->setDutyCycle(1_500_000);
$servo->setEnable(true);
```

Only the `native` driver provides PWM.

## Transports

Chip drivers depend on these contracts from `GeneralPurposeIO\Contracts`, not on any adapter. Every transport also has `close()`.

| Contract | Methods |
|---|---|
| `I2C\I2CTransport` | `probe()`, `read($len)`, `write($data)`, `writeRead($data, $len)`, `bulkWrite($messages)` |
| `SPI\SPITransport` | `read($len)`, `write($data)`, `transfer($data)` |
| `UART\UARTTransport` | `read($len)`, `write($data)`, `flush()`, `path()`, `pollBytes($max)` |
| `Digital\DigitalOutTransport` | `low()`, `high()`, `read()`, `write($state)` |
| `Digital\DigitalInTransport` | `read()`, `pollEdges($rising, $falling)`, `listen($timeout_ms, $rising, $falling)` |
| `PWM\PWMTransport` | `get`/`set` for `Period`, `DutyCycle`, `Enable` and `Polarity` |

Writes take a byte array or a binary string, and reads return a byte array, or `false` when the bus refuses. `listen()` blocks for up to `$timeout_ms` and returns one `DigitalEdgeEvent` or `null`. `pollEdges()` never waits.

A bus transport is a view on a connection its driver owns. Closing a chip driver shouldn't close the bus other devices share.

## The gpio dock

Plain transport calls block. When the Venusian IOPool dock is in the app and `gpio.io_pools.enabled` is true, the framework also registers a `gpio` resource on it. Each dock tick polls, runs and sends what drivers handed it, without waiting. Reach it through `GPIO::` or `IOPool::gpio()`.

| Call | What happens on each tick | Mail pushed |
|---|---|---|
| `watch($pin, rising: true, falling: false)` | polls an input pin for edges | `DigitalEdgeOccurrence` per edge |
| `receive($port, max_bytes: 4096)` | polls a UART port for buffered bytes | `UARTBytesOccurrence` |
| `defer($name, $work, $envelope = null)` | runs `$work` once, on the next tick | `TransferCompletion`; the returned `Presumption` settles with it |
| `every($name, $work, ticks: 1)` | runs `$work` every `$ticks` ticks until the `Recurrence` is stopped | `TransferCompletion` per run |
| `stream($name, $write, $bytes, $chunk)` | hands `$write` one `$chunk` of `$bytes` per tick | `TransferCompletion` with the bytes sent; progress on the `Presumption` |

`unwatch()`, `stopReceiving()`, `inFlight($name)`, `recurring($name)` and `streaming($name)` undo or look up each one. A name can have only one job in flight at a time.

```php
use GeneralPurposeIO\Contracts\Core\Mail\TransferCompletion;
use Voyager\IOPools\MagicAliases\IOPool;

$gpio = IOPool::gpio();

$gpio->watch($button, rising: false, falling: true);

$gpio->defer('fan.temp', fn (): array => $fan->writeRead([0xFC], 1))
    ->onSuccess(fn (TransferCompletion $done) => printf("%d °C\n", $done->result[0]))
    ->onFail(fn (TransferCompletion $done) => error_log($done->error->getMessage()));

$poll = $gpio->every('fan.poll', fn () => $fan->writeRead([0xFC], 1), ticks: 30);

while (true) {
    IOPool::pump();                    // one tick of every dock resource
    foreach (IOPool::drain() as $mail) {
        // DigitalEdgeOccurrence, TransferCompletion, …
    }
    usleep(10_000);
}

$poll->stop();
```

In a Surface `LiveApplication`, `$app->tick()` pumps the dock and `$app->events()` drains it.

Two rules keep the tick predictable:

- **Work that blocks spends the tick.** The dock never waits, but a closure that does holds up everything after it.
- **Failures become mail.** A pin, port or recurrence that throws produces a `SourceFaultOccurrence` and stays registered. A deferred job or stream that throws settles its `TransferCompletion` with the error, and `ok()` returns `false`.

Work registered during a tick runs on the next one.

## Writing chip drivers

`dept-of-scrapyard-robotics/*` packages build on these pieces:

| Class | Use |
|---|---|
| `IntegratedCircuits\Bootable` | base chip with `boot()` / `hasBooted()`; boots in the constructor when asked |
| `IntegratedCircuits\DataRegister` | readonly register breakout: `toBits()`, `toByte()`, `fromByte()`, `none()` |
| `Contracts\IntegratedCircuits\Sensor`, `Actuator`, `DisplayPanel` | what kind of chip it is |
| `Contracts\IntegratedCircuits\ReadWriter` | `read($register, $length)` / `write($register, $data)` for a chip transport |
| `Contracts\NutsAndBolts\Splices16Bits` | split 16-bit registers into bytes, and decode signed little-endian values |
| `Contracts\IntegratedCircuits\CircuitException` | base for a chip's exceptions |
| `Contracts\IntegratedCircuits\Attributes\IntegratedCircuit` | how the chip can be wired — `#[IntegratedCircuit('I2C', ['SPI', 'DigitalIO'])]` |
| `Contracts\IntegratedCircuits\Attributes\Pinout` | what each channel needs wired, index-aligned with those options |

The global helpers `array2bytes()`, `bytes2array()`, `byte2bits()` and `bits2byte()` convert between byte arrays, binary strings and bits.

## Circuits

A chip package catalogs what it ships from its provider's `boot()`; nothing is built until something asks.

```php
Circuit::addCircuit('st7789', ST7789::class);
```

The wiring lives in the app, in the config the chip package publishes:

```bash
php workshop vendor:publish --tag=st77xx-config    # config/circuits/st7789.php
```

```php
'default_config' => 'spi',
'configs' => ['spi' => [
    'driver' => 'usb', 'device' => 'ft232h', 'chip_select' => 0,
    'speed' => 10_000_000, 'width' => 320, 'height' => 240,
    'mad_ctrl' => ['pixel_direction_vertical' => true],
    'dc'  => ['driver' => 'usb', 'device' => 'ft232h', 'pin' => 1],
    'rst' => ['driver' => 'usb', 'device' => 'ft232h', 'pin' => 2],
]],
```

Then one call hands back a wired, booted chip — no adapter, bus or pin at the call site:

```php
$panel = Circuit::conjure('st7789');            // default_config
$left  = Circuit::conjure('st7789', 'left');    // a named config
```

The config entry is the signature of the chip's own `spi()` factory: its keys are passed as named arguments. A key the factory does not take is dropped; a required one the config lacks is an error that names it. Surface reaches the same door with `EmbeddedDisplay::panel('st7789')`.

## Errors

Every exception descends from `GeneralPurposeIO\Contracts\Core\GPIOLevelException`. Each protocol has its own, such as `I2CException` or `SPIException`. The `none` driver throws one that names the config key to set:

```
No I2C connection driver is configured. Set gpio.protocols.i2c.default to an installed adapter.
```

## Split packages

The framework is also published as components, for drivers that should depend on less than the whole framework. `scrapyard-io/framework` replaces them all.

| Package | Contents |
|---|---|
| `gpio/contracts` | transports, protocol enums, exceptions, the dock resource contract and its mail |
| `gpio/digital` | `DigitalIO` and its connection classes |
| `gpio/i2c` | `I2C` and its connection classes |
| `gpio/spi` | `SPI` and its connection classes |
| `gpio/uart` | `UART` and its connection classes |
| `gpio/pwm` | `PWM` and its connection classes |
| `gpio/integrated-circuits` | `Bootable`, `DataRegister`, the circuit catalog and `circuit:make-profile` |
| `gpio/nuts-and-bolts` | the byte helpers |

The aggregate service provider, the `GPIO` and `Circuit` aliases and the dock resource are part of `scrapyard-io/framework` only.

## Testing

```bash
composer install
vendor/bin/pest
```

The suite uses fake drivers and transports, so it runs without hardware or the PHP extensions.

## License

MIT. See [LICENSE](LICENSE).
