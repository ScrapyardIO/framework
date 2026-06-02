
## Setting up the UART Interface

### Native (POSIX) driver. (Single Board Computers)
```php

use Waveforms\Carriers\UART\UART;
use Waveforms\Carriers\UART\Enums\DataBits;
use Waveforms\Carriers\UART\Enums\FlowControl;
use Waveforms\Carriers\UART\Enums\Parity;
use Waveforms\Carriers\UART\Enums\StopBits;

// Native UART -> /dev/ttyAMA0
$native_uart_device = UART::connection('native')
    ->port('/dev/ttyAMA0')
    ->baud(115200)
    ->dataBits(DataBits::EIGHT)
    ->parity(Parity::NONE)
    ->stopBits(StopBits::ONE)
    ->flowControl(FlowControl::NONE)
    ->boot();

$native_uart_device->flush();
$native_uart_device->write(data: $bytes);
$native_uart_device->read(len: 64);
$native_uart_device->close();

```

### USB (FTDI) driver. (Linux and MacOS)
```php
use Waveforms\Carriers\UART\UART;
use Waveforms\Carriers\UART\Enums\DataBits;
use Waveforms\Carriers\UART\Enums\Parity;
use Waveforms\Carriers\UART\Enums\StopBits;

$usb_uart_device = UART::connection('usb')
    ->device('ft232h')
    ->baud(115200)
    ->dataBits(DataBits::EIGHT)
    ->parity(Parity::NONE)
    ->stopBits(StopBits::ONE)
    ->boot();

$usb_uart_device->flush();
$usb_uart_device->write(data: $bytes);
$usb_uart_device->read(len: 64);
$usb_uart_device->close();

```
