
## Setting up the SPI Interface

### Native (POSIX) driver. (Single Board Computers)
```php

use Waveforms\Carriers\SPI\SPI;
use Waveforms\Carriers\SPI\Enums\SPIMode;

// Native SPI -> /dev/spidev0.0
$native_spi_device = SPI::connection('native')
    ->bus(0)
    ->chip(0)
    ->mode(SPIMode::MODE_0)
    ->speed(1_000_000)
    ->bitsPerWord(8)
    ->boot();

$native_spi_device->transfer(data: $bytes); // full-duplex, returns the MISO bytes
$native_spi_device->read(len: 3);
$native_spi_device->write(data: $bytes);
$native_spi_device->close();

```

### USB (MPSSE) driver. (Linux and MacOS)
```php
use Waveforms\Carriers\SPI\SPI;
use Waveforms\Carriers\SPI\Enums\SPIMode;

$usb_spi_device = SPI::connection('usb')
    ->device('ft232h')
    ->mode(SPIMode::MODE_0)
    ->speed(1_000_000)
    ->boot();

$usb_spi_device->transfer(data: $bytes); // full-duplex, returns the MISO bytes
$usb_spi_device->read(len: 3);
$usb_spi_device->write(data: $bytes);
$usb_spi_device->close();

```
