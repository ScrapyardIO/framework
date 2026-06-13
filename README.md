# The ScrapyardIO Framework

[![Latest Version on Packagist](https://img.shields.io/packagist/v/scrapyard-io/framework.svg)](https://packagist.org/packages/scrapyard-io/framework)
[![Total Downloads](https://img.shields.io/packagist/dt/scrapyard-io/framework.svg)](https://packagist.org/packages/scrapyard-io/framework)
[![License](https://img.shields.io/packagist/l/scrapyard-io/framework.svg)](LICENSE)

A PHP 8.3+ framework for talking to real hardware — displays, sensors, GPIO pins, and serial buses — from a single-board computer or a laptop via USB. Write the same PHP code whether you are running on a Raspberry Pi or wiring up an FT232H breakout on macOS.

---

## Contents

- [Architecture](#architecture)
- [Requirements](#requirements)
- [Installation](#installation)
- [The Extension Stack](#the-extension-stack)
- [Config-based Device Loading](#config-based-device-loading)
- [Waveforms — the Transport Layer](#waveforms--the-transport-layer)
  - [SPI](#spi)
  - [I²C](#ic)
  - [GPIO](#gpio)
  - [UART](#uart)
- [RealityInterface — Displays](#realityinterface--displays)
- [RealityInterface — Sensors](#realityinterface--sensors)
- [GFX Renderer](#gfx-renderer)
- [Device Drivers (DeptOfScrapyardRobotics)](#device-drivers-deptofscrapyardrobotics)
- [License](#license)

---

## Architecture

ScrapyardIO is a layered monorepo. The `scrapyard-io/framework` package replaces four logical sub-packages via Composer's `replace` key:

| Sub-package | Purpose |
|---|---|
| `scrapyard-io/waveforms` | Protocol transports — SPI, I²C, GPIO, UART |
| `scrapyard-io/reality-interface` | Typed display and sensor abstractions |
| `scrapyard-io/nuts-and-bolts` | Pixel buffers, `FormatSpec`, enums, helpers |
| `scrapyard-io/bare-metal` | `IntegratedCircuit` base class, config repositories |

```
┌──────────────────────────────────────────────────────────────────┐
│  Application  (Laravel, CLI, Artisan commands, etc.)             │
├──────────────────────────────────────────────────────────────────┤
│  RealityInterface                                                 │
│    Displays:  Screen → Display → EmbeddedDisplay (chip driver)   │
│    Sensors:   Sensor → SensorChip (chip driver)                  │
├──────────────────────────────────────────────────────────────────┤
│  DeptOfScrapyardRobotics  (chip drivers for DOSR hardware)       │
├──────────────────────────────────────────────────────────────────┤
│  Waveforms  (SPI · I²C · GPIO · UART)                            │
├──────────────────────────────────────────────────────────────────┤
│  BareMetal  (IntegratedCircuit, config repositories)             │
├──────────────────────────────────────────────────────────────────┤
│  NutsAndBolts  (buffers · FormatSpec · enums · helpers)          │
└──────────────────────────────────────────────────────────────────┘
```

Each layer depends only on the one below it. Chip drivers (DOSR packages) sit between Waveforms and RealityInterface — they implement the chip-level contracts (`DisplayInterface`, `GenericDistanceSensor`, etc.) and expose a fluent `::connection()` builder.

**Two transport modes** run the same application code on different hardware:

| Mode | Used on | Backed by |
|---|---|---|
| `native` | Linux SBCs (Raspberry Pi, Jetson Orin, …) | `ext-posi` (POSIX syscalls) |
| `usb` | Any Linux or macOS machine with an FTDI adapter | `ext-ftdi` + `microscrap/mpsse` |

---

## Requirements

- PHP 8.3+
- For **native** (SBC) transport: [`ext-posi`](https://github.com/php-io-extensions/posi) ^0.4.0
- For **USB** (MPSSE) transport: [`ext-ftdi`](https://github.com/php-io-extensions/ftdi) ^0.4.0 + `libftdi1`

Optional Composer packages that wrap those extensions with helpers and enums:

| Package | Wraps | Adds |
|---|---|---|
| [`microscrap/posix`](https://github.com/microscrap/posix) | `ext-posi` | Global `posix_open`, `posix_read`, `fcntl`, `ioctl`, … helpers |
| [`microscrap/ftdi`](https://github.com/microscrap/ftdi) | `ext-ftdi` | Global `ftdi_usb_open`, `ftdi_read_data`, … helpers |
| [`microscrap/spi`](https://github.com/microscrap/spi) | `ext-posi` | `spi_open`, `spi_transfer`, `SPITransfer`, `SPIMode` enum |
| [`microscrap/i2c`](https://github.com/microscrap/i2c) | `ext-posi` | `i2c_open`, `i2c_rdwr`, SMBus helpers, `I2CMsgFlag` enum |
| [`microscrap/gpio`](https://github.com/microscrap/gpio) | `ext-posi` | GPIO character device helpers, edge-event polling |
| [`microscrap/uart`](https://github.com/microscrap/uart) | `ext-posi` | `uart_open`, termios helpers, `BaudRate` enum |
| [`microscrap/mpsse`](https://github.com/microscrap/mpsse) | `ext-ftdi` | `MPSSE` static API, `mpsse_open`, typed enums, ACK/NACK, GPIO pins |
| [`microscrap/phpdafruit-gfx`](https://github.com/microscrap/phpdafruit-gfx) | — | GFX renderer, partial-refresh buffers, fonts |

The framework itself (`scrapyard-io/framework`) has no hard PHP-extension dependencies — it composes these optional packages at runtime.

---

## Installation

```bash
composer require scrapyard-io/framework
```

Install the DOSR chip drivers you need alongside it:

```bash
composer require dept-of-scrapyard-robotics/ssd1680  # ePaper B/W (122×250)
composer require dept-of-scrapyard-robotics/ssd1306  # OLED I²C/SPI
composer require dept-of-scrapyard-robotics/st77xx    # RGB565 TFT
composer require dept-of-scrapyard-robotics/vl53lxx  # Time-of-flight distance
composer require dept-of-scrapyard-robotics/hc-sr04  # Ultrasonic distance
# … etc.
```

---

## The Extension Stack

ScrapyardIO's native transport is built on two PHP C extensions.

### `ext-posi` — POSIX I/O

[`php-io-extensions/posi`](https://github.com/php-io-extensions/posi) provides direct access to POSIX system calls from PHP: `open(2)`, `read(2)`, `write(2)`, `fcntl(2)`, `ioctl(2)`, and more. It is the foundation for all native SBC transports (SPI via `spidev`, I²C via `i2c-dev`, UART via `termios`, GPIO via `gpiod`).

Build and install on target platforms:

```bash
# Raspberry Pi / Debian
bash install-debian-trixie.sh

# Jetson Orin (JetPack 6)
bash install-jetpack6.sh

# macOS
bash install-macos.sh

# Or via PIE
pie install php-io-extensions/posi
```

Then add to `php.ini`:

```ini
extension=posi
```

`microscrap/posix` wraps the extension with C-style global helpers so calling code reads naturally:

```php
$fd = posix_open('/dev/spidev0.0', O_RDWR);
posix_write($fd, $payload, strlen($payload));
posix_close($fd);
```

### `ext-ftdi` — libftdi1 Bindings

[`php-io-extensions/ftdi`](https://github.com/php-io-extensions/ftdi) wraps [libftdi1](https://www.intra2net.com/en/developer/libftdi/) so PHP can drive FTDI USB adapters (FT232H, FT232RL, etc.) directly. It powers the `usb` transport path — SPI, I²C, and GPIO all tunnelled over USB MPSSE.

Runtime library requirements:

```bash
# Debian/Ubuntu/Raspberry Pi OS
apt install libftdi1-2

# macOS
brew install libftdi
```

`microscrap/ftdi` adds global helpers and `microscrap/mpsse` layers the higher-level MPSSE session API (start/stop, read/write, GPIO pin control) on top of them:

```php
use Microscrap\Bindings\MPSSE\MPSSE;
use Microscrap\Bindings\MPSSE\Enums\{MPSSEMode, MPSSEEndianness, MPSSEClockRate, MpsseSupportedDevice};

$ctx = MPSSE::openDevice(
    MpsseSupportedDevice::FT232H,
    MPSSEMode::SPI0,
    MPSSEClockRate::ONE_MHZ->value,
    MPSSEEndianness::MSB,
);

MPSSE::start($ctx);
MPSSE::write($ctx, "\x9F");
$id = MPSSE::read($ctx, 3);
MPSSE::stop($ctx);
MPSSE::close($ctx);
```

---

## Config-based Device Loading

For production deployments where chips are permanently wired, you can preload all device definitions from a config file rather than constructing them inline.

**1. Set the config path in `.env`:**

```dotenv
SCRAPYARD_CONFIG_PATH=/home/pi/my-project/config
```

**2. Create `scrapyard-io.php` in that directory:**

```php
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X;

return [
    'displays' => [
        'eink-panel' => [
            'class_name' => SSD1680::class,
            'connection' => ['driver' => 'usb'],
            'startup' => [
                'spi'      => ['ft232h', 0],
                'gpiochip' => ['ft232h'],
                'rst'      => [0],
                'dc'       => [1],
                'create'   => [],
            ],
        ],
    ],
    'sensors' => [
        'tof-sensor' => [
            'class_name' => VL53L0X::class,
            'connection' => ['driver' => 'usb'],
            'startup' => [
                'i2c'    => ['ft232h', 0x29],
                'create' => [],
            ],
        ],
    ],
];
```

**3. Resolve devices by config key:**

```php
use RealityInterface\Displays\BWePaperDisplay;
use RealityInterface\Sensors\TimeOfFlightDistanceSensor;

$display = BWePaperDisplay::using('eink-panel');
$sensor  = TimeOfFlightDistanceSensor::using('tof-sensor');
```

Consult each DOSR package's README for the exact `startup` key shape for that chip.

---

## Waveforms — the Transport Layer

`Waveforms\` provides the four bus protocols that chip drivers build on. Each has a `native` path (Linux kernel drivers via `ext-posi`) and a `usb` path (FT232H via `ext-ftdi` + `microscrap/mpsse`).

### SPI

```php
use Waveforms\Carriers\SPI\SPI;

// Native — spidev on a Raspberry Pi
$spi = SPI::connection('native')
    ->device('/dev/spidev0.0')
    ->mode(0)
    ->speed(8_000_000)
    ->create();

// USB — FT232H via MPSSE
$spi = SPI::connection('usb')
    ->device('ft232h')
    ->speed(1_000_000)
    ->create();
```

Under the hood the native path uses `microscrap/spi` (`spi_open`, `spi_transfer`). The USB path opens an MPSSE session in `SPI0` mode.

### I²C

```php
use Waveforms\Carriers\I2C\I2C;

// Native — i2c-dev on a Raspberry Pi
$i2c = I2C::connection('native')
    ->bus('/dev/i2c-1')
    ->address(0x38)
    ->create();

// USB — FT232H via MPSSE (I2C mode)
$i2c = I2C::connection('usb')
    ->device('ft232h')
    ->address(0x38)
    ->create();
```

### GPIO

```php
use Waveforms\Carriers\GPIO\GPIO;
use Waveforms\Carriers\GPIO\API\Native\{NativeGPIOInput, NativeGPIOOutput};
use Waveforms\Carriers\GPIO\API\USB\{UsbGPIOInput, UsbGPIOOutput};

// Native — Linux gpiod character device
$echo = NativeGPIOInput::line(22)->edgeEvents()->alias('echo');
$trig = NativeGPIOOutput::line(24)->alias('trig');

$bus = GPIO::connection('native')
    ->gpiochip(0)
    ->addInput($echo)
    ->addOutput($trig)
    ->nonblocking()
    ->consumer('distance-sensor')
    ->boot();

$bus->trig()->high();
usleep(10);
$bus->trig()->low();
$value = $bus->echo()->read();

// USB — FT232H CBUS/ADBUS GPIO pins
$echo = UsbGPIOInput::pin(0)->edgeEvents()->alias('echo');
$trig = UsbGPIOOutput::pin(1)->alias('trig');

$bus = GPIO::connection('usb')
    ->device('ft232h')
    ->addInput($echo)
    ->addOutput($trig)
    ->consumer('distance-sensor')
    ->boot();
```

### UART

```php
use Waveforms\Carriers\UART\UART;

// Native — /dev/ttyUSB0 via termios
$uart = UART::connection('native')
    ->device('/dev/ttyUSB0')
    ->baud(115200)
    ->create();

// USB — FT232RL serial over libftdi
$uart = UART::connection('usb')
    ->device('ft232rl')
    ->baud(115200)
    ->create();
```

---

## RealityInterface — Displays

`RealityInterface\Displays\` provides the display abstraction stack. Application code works with the applied classes (`BWePaperDisplay`, `ColorTFTDisplay`, etc.) and never touches chip registers directly.

### Applied display classes

| Class | Pixel format | Chip contracts it targets |
|---|---|---|
| `MonochromeDisplay` | 1bpp mono | SSD1306, SH1106 (OLED) |
| `ColorTFTDisplay` | RGB565 | ST7735, ST7789, ST7796, GC9A01 |
| `BWePaperDisplay` | 1bpp B/W | SSD1680, IL3820 |
| `TriColorePaperDisplay` | 1bpp B/W + 1bpp red | SSD1680 (B/W/R mode) |
| `QuadColorePaperDisplay` | 2bpp B/W/R/Y | JD79661 |

### Constructing a display

```php
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680;
use RealityInterface\Displays\BWePaperDisplay;

// Inline — useful during development
$chip = SSD1680::connection('usb')
    ->spi('ft232h', 0)
    ->gpiochip('ft232h')
    ->rst(0)
    ->dc(1)
    ->create();

$display = BWePaperDisplay::as($chip);
```

### Drawing with `Screen`

`Screen` is the fluent drawing surface. It wraps a `GFXRenderer` (from `microscrap/phpdafruit-gfx`), transcodes the rendered buffer through the display's `FormatSpec`, and ships the bytes to the panel.

```php
use RealityInterface\Displays\Screen;

$screen = Screen::on($display);

$screen
    ->fill(0x00)
    ->drawRect(2, 2, 118, 60, 0xFF)
    ->setTextColor(0xFF)
    ->setCursor(10, 20)
    ->print('Hello from PHP')
    ->render();
```

`render()` calls `display->transmit(DumpedBuffer)` internally; partial-refresh panels receive only dirty pages/regions.

---

## RealityInterface — Sensors

`RealityInterface\Sensors\` mirrors the display stack for sensor chips. Each applied class carries a capability attribute that chip drivers must declare.

### Applied sensor classes

| Class | Capability | Chip contract |
|---|---|---|
| `DistanceSensor` | `MeasuresDistance` | `GenericDistanceSensor` |
| `UltrasonicDistanceSensor` | extends `DistanceSensor` | `PulseDerivedDistanceSensor` |
| `TimeOfFlightDistanceSensor` | extends `DistanceSensor` | `LaserGuidedDistanceSensor` |
| `Accelerometer` | `MeasuresAcceleration` | `GenericAccelerometer` |
| `AmbientLightSensor` | `MeasuresLuminance` | `GenericLuxSensor` |
| `TemperatureSensor` | `MeasuresTemperature` | `TemperatureSensor` |
| `RelativeHumiditySensor` | `MeasuresRelativeHumidity` | `RHSensor` |
| `BarometricPressureSensor` | `MeasuresBarometricPressure` | `PressureSensor` |
| `RFIDScanner` | `NearFieldCommunications` | `GenericRFIDScanner` |

### Reading a sensor

```php
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X;
use RealityInterface\Sensors\TimeOfFlightDistanceSensor;

$chip   = VL53L0X::connection('native')->i2c('/dev/i2c-1', 0x29)->create();
$sensor = TimeOfFlightDistanceSensor::as($chip);

$reading = $sensor->getDistance();
echo $reading->millimeters() . ' mm';
```

---

## GFX Renderer

[`microscrap/phpdafruit-gfx`](https://github.com/microscrap/phpdafruit-gfx) is the rendering engine used by `Screen`. It is modelled on [Adafruit-GFX](https://github.com/adafruit/Adafruit-GFX-Library) and draws into a `FormatSpecFrameBuffer`. The buffer's `FormatSpec` determines how pixels are packed on `dump()` — the same drawing calls produce 1bpp mono page buffers for an OLED or RGB565 for a TFT.

### Primitives

| Category | Methods |
|---|---|
| Pixels | `drawPixel`, `drawPixels`, `fill`, `fillScreen` |
| Lines | `drawLine`, `drawHLine`, `drawVLine`, `drawLines` |
| Rectangles | `drawRect`, `fillRect`, `drawRoundRect`, `fillRoundRect` |
| Curves | `drawCircle`, `fillCircle`, `drawEllipse`, `fillEllipse` |
| Triangles | `drawTriangle`, `fillTriangle` |
| Text | `print`, `println`, `setCursor`, `setTextColor`, `setTextSize`, `setFont` |
| Bitmaps | `drawBitmap`, `drawXBitmap`, `drawColorMap` |
| Images | `drawImageFromFile` (requires `ext-gd`) |
| Dithering | `ditherFloydSteinberg`, `drawImageDithered` (requires `ext-gd`) |

### Partial-refresh buffers

| Buffer | Use case |
|---|---|
| `PageSegmentBuffer` | Monochrome OLED panels — emits one update per dirty 8-row page |
| `DirtyRegionsBuffer` | Color TFTs — coalesces changed rectangles, emits one update per region |

```php
use Microscrap\GFX\PhpdaFruit\GFXRenderer;
use Microscrap\GFX\PhpdaFruit\Buffers\PageSegmentBuffer;
use ScrapyardIO\NutsAndBolts\Enums\{BitDepth, BitOrder, PixelFormat};

$buffer = PageSegmentBuffer::size(128, 64)
    ->pixelFormat(PixelFormat::MONO_VERTICAL_PAGE)
    ->bitDepth(BitDepth::B1)
    ->bitOrder(BitOrder::LSB_FIRST)
    ->build();

$renderer = new GFXRenderer($buffer);
$renderer->fill(0)->drawCircle(64, 32, 20, 1)->print('ok');

$updates = $renderer->render(); // array of DumpedBuffer
```

---

## Device Drivers (DeptOfScrapyardRobotics)

All chip drivers live in separate `dept-of-scrapyard-robotics/*` packages. Every driver follows the same fluent builder pattern and plugs cleanly into the `RealityInterface` applied classes above.

### Displays

| Package | Chips | Wrapper |
|---|---|---|
| `ssd1680` | SSD1680 — 122×250 ePaper | `BWePaperDisplay`, `TriColorePaperDisplay` |
| `jd79661` | JD79661 — 2bpp quad-color ePaper | `QuadColorePaperDisplay` |
| `st77xx` | ST7735 (128×160), ST7789 (240×320), ST7796 (480×320) | `ColorTFTDisplay` |
| `gc9a01` | GC9A01 — 240×240 round TFT | `ColorTFTDisplay` |
| `ssd1306` | SSD1306 — monochrome OLED (I²C or SPI) | `MonochromeDisplay` |
| `sh1106` | SH1106 — monochrome OLED (I²C) | `MonochromeDisplay` |
| `il3820` | IL3820 — ePaper controller | ePaper wrappers |

### Sensors

| Package | Chips | Type | Wrapper |
|---|---|---|---|
| `vl53lxx` | VL53L0X, VL53L1X, VL6180X | Time-of-flight (I²C) | `TimeOfFlightDistanceSensor` |
| `hc-sr04` | HC-SR04 | Ultrasonic (GPIO) | `UltrasonicDistanceSensor` |
| `adxl34x` | ADXL345 | Accelerometer (I²C/SPI) | `Accelerometer` |
| `lis3dx` | LIS3DH, LIS3DSH | Accelerometer (I²C/SPI) | `Accelerometer` |
| `msa3xx` | MSA311, MSA301 | Accelerometer (I²C) | `Accelerometer` |
| `bh1750` | BH1750 | Ambient light (I²C) | `AmbientLightSensor` |
| `tsl2591` | TSL2591 | Dual-channel lux (I²C) | `AmbientLightSensor` |
| `veml7000` | VEML7000 | Ambient light (I²C) | `AmbientLightSensor` |
| `ahtx0` | AHT10, AHT20, AHT30 | Temp + RH (I²C) | `TemperatureSensor`, `RelativeHumiditySensor` |
| `bmp` | BMP280 | Temp + pressure (I²C/SPI) | `TemperatureSensor`, `BarometricPressureSensor` |
| `as3935` | AS3935 | Lightning detector (SPI) | Direct (no typed wrapper) |
| `rfid` | NFC/RFID controllers | I²C/SPI/UART + GPIO | `RFIDScanner` |

Each package's README documents the exact `::connection()` builder chain and the `scrapyard-io.php` config shape for that chip.

---

## License

[MIT](LICENSE)
