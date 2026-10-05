# scrapyard-io/framework

[![Tests](https://github.com/ScrapyardIO/framework/actions/workflows/tests.yml/badge.svg)](https://github.com/ScrapyardIO/framework/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/scrapyard-io/framework.svg)](https://packagist.org/packages/scrapyard-io/framework)
[![License](https://img.shields.io/packagist/l/scrapyard-io/framework.svg)](LICENSE)
[![Docs](https://img.shields.io/badge/docs-ScrapyardIO-0ea5e9?logo=readthedocs&logoColor=white)](https://scrapyard-io.projectsaturnstudios.com/ecosystem/scrapyard-io/framework/0.8.x/overview)

Talk to hardware from a Venusian app: I2C, SPI, UART, digital pins and PWM behind one API, whether the bus is a Raspberry Pi's own or an FTDI USB board.

`scrapyard-io/framework` gives each protocol a connection manager and defines the transports chip drivers are written against. Adapter packages plug the actual hardware in. Every call blocks by default. When the app has an IOPools event loop, waits share the loop, pins and ports can mail what they receive, and bus work can be offloaded to a worker pool.

```
ext-posi / ext-ftdi            1:1 system and libftdi calls
  → microscrap/*               libgpiod, i2c-dev, spidev, termios, libmpsse in PHP
    → microscrap/scrapyard-*   adapters: the `native` and `usb` drivers
      → scrapyard-io/framework protocol managers, transports, circuits   ← this package
        → dept-of-scrapyard-robotics/*   chip drivers
```

## Requirements

- PHP 8.4 or newer
- A Venusian 0.10 application (`venusian-voyager/*` 0.10)
- At least one adapter:

| Adapter | Driver name | Protocols | Hardware |
|---|---|---|---|
| `microscrap/scrapyard-linux` | `native` | I2C, SPI, UART, digital, PWM | Linux `i2c-dev`, `spidev`, serial ports, `libgpiod`, sysfs PWM; needs `ext-posi` |
| `microscrap/scrapyard-usb` | `usb` | I2C, SPI, UART, digital | FTDI boards such as the FT232H: MPSSE for I2C, SPI and pins, the UART engine for serial; needs `ext-ftdi` |

Without an adapter, every protocol uses the built-in `none` driver, which throws as soon as you try to connect.

## Installation

```bash
composer require scrapyard-io/framework
```

The service provider is discovered automatically. It binds one connection manager per protocol and the circuit catalog:

| Container key | Class |
|---|---|
| `gpio.i2c` | `GeneralPurposeIO\I2C\I2CConnectionManager` |
| `gpio.spi` | `GeneralPurposeIO\SPI\SPIConnectionManager` |
| `gpio.uart` | `GeneralPurposeIO\UART\UARTConnectionManager` |
| `gpio.digital` | `GeneralPurposeIO\Digital\DigitalOConnectionManager` |
| `gpio.pwm` | `GeneralPurposeIO\PWM\PWMConnectionManager` |
| `circuit` | `GeneralPurposeIO\IntegratedCircuits\CircuitRegistry` |

Each adapter registers its driver name on every manager it supports.

### Setting up a machine

Two dev commands install what the hardware side needs, using the PHP binary that runs `computer`:

```bash
php computer scrapyard:ext        # ext-posi and ext-ftdi, through PIE
php computer scrapyard:modules    # the microscrap packages this machine can run, through Composer
```

`scrapyard:ext` lists both extensions and installs the ones you pick with [PIE](https://github.com/php/pie), offering to download PIE when it is missing. An extension already loaded at 0.10 shows as installed; one loaded at an older version is offered as a replacement. Neither builds on Windows.

`scrapyard:modules` looks at the operating system and the loaded extensions and offers what they allow:

| Module | Needs |
|---|---|
| `microscrap/gpio`, `i2c`, `spi`, `uart` | ext-posi 0.10 |
| `microscrap/mpsse` | ext-ftdi 0.10 |
| `microscrap/scrapyard-linux` | Linux and ext-posi 0.10 |
| `microscrap/scrapyard-usb` | ext-ftdi 0.10 |

The picked ones go into your app with a single `composer require`. Name one to skip the list, which also works without a terminal: `php computer scrapyard:ext ftdi`, `php computer scrapyard:modules scrapyard-usb`.

### Default drivers

To choose default drivers, publish the config:

```bash
php computer vendor:publish --tag=gpio-config
```

```php
// config/gpio.php
return [
    'protocols' => [
        'uart' => ['default' => 'native'],
        'i2c' => ['default' => 'native'],
        'spi' => ['default' => 'native'],
        'digital-in' => ['default' => 'native'],   // the gpio.digital default
        'digital-out' => ['default' => 'native'],
        'pwm' => ['default' => 'native'],
    ],
];
```

`driver()` with no name uses the default. Naming the driver, as the examples below do, works whatever the defaults are.

## Quick start

Read a register from a device at `0x21` on a Raspberry Pi's `i2c-1`:

```php
$device = app('gpio.i2c')->driver('native')
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
3. `register()` opens the connection and stores it on the driver. The driver hands out transports from it until the app ends or you call `disconnect($device)`.
4. `device(...)` returns a transport for one address, chip select, port, pin or channel. It returns `null` if that device was never registered.

Connecting the same device twice throws. Register a bus once, then call `device()` as often as you need.

### I2C

```php
$bus = app('gpio.i2c')->driver('native')->connectTo(1)->register();
$display = $bus->device(1, 0x3C);
$fan = $bus->device(1, 0x21);

$ft232h = app('gpio.i2c')->driver('usb')->connectTo('ft232h')->register()->device('ft232h', 0x53);
```

Transports on the same bus share one connection. Each call addresses its own device.

### SPI

```php
use GeneralPurposeIO\Contracts\SPI\SPIMode;

$panel = app('gpio.spi')->driver('native')
    ->connectTo(0)              // /dev/spidev0.*
    ->mode(SPIMode::MODE_0)     // or 0–3
    ->speed(8_000_000)          // Hz
    ->register()
    ->device(0, 0);             // chip select 0
```

`endianness()` and `chipSelect()` are also available. The `usb` driver takes `speed()` in Hz too, or an `MPSSEClockRate` through `clockRate()`.

A slave can run at its own clock with `$panel->speed($hz)`, and `$panel->select(fn () => ...)` holds its chip select across every call the closure makes.

### UART

```php
use GeneralPurposeIO\Contracts\UART\Parity;

$gps = app('gpio.uart')->driver('native')
    ->connectTo('/dev/ttyAMA0')
    ->baud(9600)
    ->parity(Parity::NONE)
    ->register()
    ->device('/dev/ttyAMA0');

$gps->write("\$PMTK605*31\r\n");
$line = $gps->readUntil("\r\n", timeout_ms: 1000);   // null on timeout
```

`stopBits()`, `dataBits()` and `flowControl()` take their enums or the matching integers. The defaults are 9600 baud, 8 data bits, no parity, 1 stop bit and no flow control.

On the `usb` driver, name the FTDI product instead of a path: `connectTo('ft232h')`. One FTDI interface runs either its UART engine or MPSSE, so a port there refuses I2C, SPI and pins on the same interface until it is disconnected.

Each port keeps one unread buffer of 64 KB. When it overflows, the oldest bytes go and `dropped()` counts them. `dtr()` and `rts()` drive the modem lines.

### Digital pins

```php
use GeneralPurposeIO\Contracts\Digital\LineBias;

$pins = app('gpio.digital')->driver('native')->connectTo(0)->register();   // gpiochip0
$led = $pins->output(0, 17);
$button = $pins->input(0, 27, LineBias::PULL_UP);

$led->high();
$pressed = ! $button->read();
$edge = $button->listen(500, rising_events: false, falling_events: true);   // DigitalEdgeEvent or null
```

`input()` also takes `active_low`, which inverts the level the pin reports. `pollEdges()` returns the unread edges without waiting.

On an FTDI board, an I2C or SPI connection already registers the device, so its GPIOL pins are available straight away:

```php
$dc = app('gpio.digital')->driver('usb')->output('ft232h', 1);
```

### PWM

```php
$servo = app('gpio.pwm')->driver('native')->connectTo(0)->register()->device(0, 0);   // pwmchip0, channel 0

$servo->setPeriod(20_000_000);      // nanoseconds
$servo->setDutyCycle(1_500_000);
$servo->setEnable(true);
```

Only the `native` driver provides PWM.

## Transports

Chip drivers depend on these contracts from `GeneralPurposeIO\Contracts`, not on any adapter. Every transport also has `close()`.

| Contract | Methods |
|---|---|
| `I2C\I2CTransport` | `probe()`, `read($len)`, `write($data)`, `writeRead($data, $len)`, `bulkWrite($messages)`, `via($target)` |
| `SPI\SPITransport` | `read($len)`, `write($data)`, `transfer($data)`, `writeRead($data, $len)`, `select($body)`, `speed($hz)`, `chipSelect()`, `via($target)` |
| `UART\UARTTransport` | `read($len, $timeout_ms)`, `readUntil($delimiter, $timeout_ms)`, `write($data, $timeout_ms)`, `pollBytes($max)`, `flush()`, `dropped()`, `dtr($on)`, `rts($on)`, `path()`, `watch()`, `unwatch()` |
| `Digital\DigitalOutTransport` | `low()`, `high()`, `read()`, `write($state)` |
| `Digital\DigitalInTransport` | `read()`, `pollEdges($rising, $falling)`, `listen($timeout_ms, $rising, $falling)`, `watch($rising, $falling)`, `unwatch()` |
| `PWM\PWMTransport` | `get`/`set` for `Period`, `DutyCycle`, `Enable` and `Polarity`, `channel()`, `via($target)` |

Writes take a byte array or a binary string, and reads return a byte array, or `false` when the bus refuses. Timeouts are in milliseconds: `-1` waits without limit and `0` never waits. A UART `read()` returns `[]` on timeout, and `write()` throws once its timeout passes with bytes unsent; the exception carries how many went out.

A bus transport is a view on a connection its driver owns. Closing a chip driver shouldn't close the bus other devices share.

## The event loop

Without an IOPools event loop bound in the container, every wait blocks the process in the adapter. Once `Voyager\Contracts\IOPools\Loop` is bound, the same calls share the loop instead:

- **Waits stay out of the way.** `listen()`, UART `read()`, `readUntil()` and `write()` suspend their fiber inside `$loop->async()`. On the main stack they turn the loop until they finish, so timers and other resources keep running.
- **Pins and ports can mail.** `watch()` puts an input pin or a UART port on the loop, and `unwatch()` takes it off. Each edge or chunk is mailed and also stays readable through `listen()` or `read()`. `watch()` throws without a loop.
- **Bus work can be offloaded.** `via()` returns the same calls as promises.

| Watched | Resource name | Mail |
|---|---|---|
| input pin | `gpio.edge.<device>.<pin>` | `DigitalEdgeEvent`: `device`, `pin`, `edge`, `timestamp`, `seqno` |
| UART port | `gpio.uart.<device>` | `UARTReceived`: `device`, `bytes`, `timestamp`, `seqno` |

Mail is delivered on the turns of `$loop->run()`, through the loop's mail handler. The default handler dispatches each item as a signal under its resource name, so a listener can take one port, a wildcard, or the class. A wildcard listener receives the name and the mail in an array:

```php
use GeneralPurposeIO\Contracts\UART\UARTReceived;
use Voyager\Contracts\IOPools\Loop;

app('signals')->listen('gpio.uart./dev/ttyAMA0', fn (UARTReceived $chunk) => print $chunk->bytes);
app('signals')->listen('gpio.edge.*', fn (string $name, array $mail) => printf("%s %s\n", $name, $mail[0]->edge->value));
app('signals')->listen(UARTReceived::class, fn (UARTReceived $chunk) => error_log(strlen($chunk->bytes).' bytes'));

$gps->watch();
$button->watch(rising_events: false, falling_events: true);
app(Loop::class)->run();
```

Both mail classes round-trip through `toData()` and `fromData()`, so they cross a worker or queue wire intact.

### Offloading with via()

`via($pool)` hands a transport's calls to an IOPools worker pool and returns a `Voyager\Contracts\IOPools\Promise`. `$pool` is `'thread'` or `'process'`. `null` uses the adapter's own async path where it has one (the USB adapters' pump), and otherwise the thread pool when it is on and the process pool otherwise.

```php
$fan->via('process')->writeRead([0xFC], 1)
    ->then(fn (array $bytes) => printf("%d °C\n", $bytes[0]))
    ->error(fn (Throwable $e) => error_log($e->getMessage()));
```

- Jobs for one slave run one at a time, in call order; different slaves run side by side.
- A blocking call on a slave first waits for the jobs queued on it before the call.
- `close()` lets the running job finish and rejects the queued ones.
- A worker's exception comes back as the scrapyard exception it was.

A chip driver ships its own multi-step work as a `Contracts\NutsAndBolts\BusJob` and runs it with `via()->run($job)`. `run(GPIOTransport $bus)` receives the transport on whichever side of the wire the job lands.

`via()` needs a bound loop and a worker pool that is on (`io-pools.pool_workers.threads` or `.process`); without them it throws an exception that says which is missing.

## Writing chip drivers

`dept-of-scrapyard-robotics/*` packages build on these pieces:

| Class | Use |
|---|---|
| `IntegratedCircuits\IntegratedCircuit` | base class for every chip |
| `IntegratedCircuits\Bootable` | a chip with `boot()` / `hasBooted()`: implement `_boot()`; boots in the constructor when `$boot_now` is true |
| `IntegratedCircuits\DataRegister` | readonly register breakout: `toBits()`, `toByte()`, `fromByte()`, `none()` |
| `Contracts\IntegratedCircuits\Sensor`, `Actuator`, `DisplayPanel` | what kind of chip it is |
| `Contracts\IntegratedCircuits\Switchable`, `WindowAddressable`, `PipeablePanel`, `RefreshesOnCommand` | what a display panel can do; a `PipeablePanel` is fed straight from memory over a `Contracts\SPI\WritesFromMemory` bus |
| `Contracts\IntegratedCircuits\ReadWriter` | `read($register, $length)` / `write($register, $data)` for a chip transport |
| `Contracts\IntegratedCircuits\DataCommander` | `data($bytes)` / `command($register, $data)` for a chip with a data/command line |
| `Contracts\NutsAndBolts\Splices16Bits` | split 16-bit registers into bytes, and decode signed little-endian values |
| `Contracts\NutsAndBolts\BusJob` | multi-step bus work that `via()->run()` can offload |
| `Contracts\IntegratedCircuits\CircuitException` | base for a chip's exceptions |

The global helpers `array2bytes()`, `bytes2array()`, `byte2bits()` and `bits2byte()` convert between byte arrays, binary strings and bits.

## Circuits

A chip package catalogs what it ships from its provider's `boot()`; nothing is built until something asks.

```php
app('circuit')->addCircuit('st7789', ST7789::class);
```

The wiring lives in the app, in the config the chip package publishes (`config/circuits/st7789.php`):

```php
'default_config' => 'spi',
'configs' => ['spi' => [
    'driver' => 'usb', 'device' => 'ft232h', 'chip_select' => 0,
    'speed' => 10_000_000, 'width' => 320, 'height' => 240,
    'dc'  => ['driver' => 'usb', 'device' => 'ft232h', 'pin' => 1],
    'rst' => ['driver' => 'usb', 'device' => 'ft232h', 'pin' => 2],
]],
```

Then one call hands back a wired chip, with no adapter, bus or pin at the call site:

```php
$panel = app('circuit')->conjure('st7789');            // default_config
$left  = app('circuit')->conjure('st7789', 'left');    // a named config
```

A config is named after its protocol unless it sets `'protocol'`, and the chip's public static factory of that name builds it: the config's keys are passed as named arguments. A key the factory does not take is dropped; a required one the config lacks is an error that names it. `build($slug, $protocol, $params)` does the same from an array.

## Errors

Every exception descends from `GeneralPurposeIO\Contracts\Core\GPIOLevelException`. Each protocol has its own, such as `I2CException` or `UARTException`. The `none` driver throws one that names the config key to set:

```
No I2C connection driver is configured. Set gpio.protocols.i2c.default to an installed adapter.
```

## Split packages

The framework is also published as components, for drivers that should depend on less than the whole framework. `scrapyard-io/framework` replaces them all.

| Package | Contents |
|---|---|
| `gpio/contracts` | transports, offloaded transports, protocol enums, exceptions, `BusJob` and the mail classes |
| `gpio/digital` | the `gpio.digital` manager and its connection classes |
| `gpio/i2c` | the `gpio.i2c` manager and its connection classes |
| `gpio/spi` | the `gpio.spi` manager and its connection classes |
| `gpio/uart` | the `gpio.uart` manager and its connection classes |
| `gpio/pwm` | the `gpio.pwm` manager and its connection classes |
| `gpio/integrated-circuits` | `IntegratedCircuit`, `Bootable`, `DataRegister` and the `circuit` catalog |
| `gpio/nuts-and-bolts` | the byte helpers |

The aggregate service provider and `config/gpio.php` are part of `scrapyard-io/framework` only.

## Testing

```bash
composer install
vendor/bin/pest
```

The suite uses fake drivers and transports, so it runs without hardware or the PHP extensions.

## License

MIT. See [LICENSE](LICENSE).
