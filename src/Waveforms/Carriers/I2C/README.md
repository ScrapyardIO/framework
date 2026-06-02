
## Setting up the I2C Interface

### Native (POSIX) driver. (Single Board Computers)
```php

use Waveforms\Carriers\I2C\I2C;

// Native I2C
$native_i2c_device = I2C::connection('native')
    ->master(0)
    ->slaveAddress(0x38)
    ->boot();
    
$native_i2c_device->read(len: 1);
$native_i2c_device->write(data: $bytes);
$native_i2c_device->readWrite(bytes_to_write: $bytes, bytes_to_read: 1);

```

### USB (MPSSE) driver. (Linux and MacOS)
```php
use Waveforms\Carriers\I2C\I2C;

$usb_i2c_device = I2C::connection('usb')
    ->device('ft232h')
    ->slaveAddress(0x38)
    ->boot();

$usb_i2c_device->read(len: 1);
$usb_i2c_device->write(data: $bytes);
$usb_i2c_device->readWrite(bytes_to_write: $bytes, bytes_to_read: 1);

```
