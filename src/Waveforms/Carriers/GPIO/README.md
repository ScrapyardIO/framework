
## Setting up GPIO Pins

### Native (POSIX) driver. (Single Board Computers)
```php

use Waveforms\Carriers\GPIO\GPIO;
use Waveforms\Carriers\GPIO\API\Native\NativeGPIOInput;
use Waveforms\Carriers\GPIO\API\Native\NativeGPIOOutput;

// Native GPIO 
$gpio_output = NativeGPIOOutput::line(24)->alias('trig');
$gpio_input = NativeGPIOInput::line(22)->edgeEvents()->nonblocking()->alias('echo');

$native_gpio_bus = GPIO::connection('native')
    ->gpiochip(0)
    ->addInput($gpio_input)
    ->addOutput($gpio_output)
    ->consumer('distance-sensor')
    ->boot();

$native_gpio_bus->trig()->high();
$native_gpio_bus->trig()->low();

// @stop here until above is implemented.

$value = $native_gpio_bus->echo()->read();
$event = $native_gpio_bus->echo()->listen()

```

### USB (MPSSE) driver. (Linux and MacOS)
```php

use Waveforms\Carriers\GPIO\GPIO;
use Waveforms\Carriers\GPIO\API\USB\UsbGPIOInput;
use Waveforms\Carriers\GPIO\API\USB\UsbGPIOOutput;

// USB GPIO
$gpio_input = UsbGPIOInput::pin(0)->edgeEvents()->alias('echo');
$gpio_output = UsbGPIOOutput::pin(1)->alias('trig');

$usb_gpio_bus = GPIO::connection('usb')
    ->device('ft232h')
    ->addInput($gpio_input)
    ->addOutput($gpio_output)
    ->consumer('distance-sensor')
    ->boot();

$usb_gpio_bus->trig()->high();
$usb_gpio_bus->trig()->low();

// @stop here until above is implemented.
    
$value = $usb_gpio_bus->echo()->read();
$event = $usb_gpio_bus->echo()->listen()

```
