# The ScrapyardIO Framework

[![Latest Version on Packagist](https://img.shields.io/packagist/v/scrapyard-io/framework.svg)](https://packagist.org/packages/scrapyard-io/framework)
[![Total Downloads](https://img.shields.io/packagist/dt/scrapyard-io/framework.svg)](https://packagist.org/packages/scrapyard-io/framework)
[![License](https://img.shields.io/packagist/l/scrapyard-io/framework.svg)](LICENSE)

## About ScrapyardIO

> **Note:** This repository contains the core code of the ScrapardIO framework. 


### Autoloading Integrated Circuit configs

<p> 
ScrapyardIO supports preloading IntegratedCircuit definitions, for streamlined
instatiating of devices especially in use-cases where the IntegratedCircuits are being
used in production, that is, they are physically mapped to their pins indefinitely,
rather than in development, where the pins might change.
</p>


<p>
In order to do this, the framework must be plugged into a project where the .ENV gets autoloaded.
And add the following variable, subbing the example string to the actual path of your project
</p>

```dotenv

SCRAPYARD_CONFIG_PATH=/home/pi/path/to/project/config

```

<p> In the folder you decide, make a file called scrapyard-io.php and start it with - </p>

```php

return [
    'boards' => [
        'something' => [
            'class_name' => SomeCircuit::class,
            'connection' => ['driver' => 'native'|'usb',...]
            'startup' => [
                'method0' => [...$args],
                'method1' => [...$args],
                //...
            ],
        ]       
    ],
];

```

<p> Consult with DeptOfScrapyardRobotics IntegratedCircuit packages' READMEs with the actual shape to define the devices you need to use </p>


## Setting up GPIO Pins

### Native (POSIX) driver. (Single Board Computers)
```php

use Waveforms\Carriers\GPIO\GPIO;
use Waveforms\Carriers\GPIO\API\Native\NativeGPIOInput;
use Waveforms\Carriers\GPIO\API\Native\NativeGPIOOutput;

// Native GPIO 
$gpio_input = NativeGPIOInput::line(22)->edgeEvents()->alias('echo');
$gpio_output = NativeGPIOOutput::line(24)->alias('trig');

$native_gpio_bus = GPIO::connection('native')
    ->gpiochip(0)
    ->addInput($gpio_input)
    ->addOutput($gpio_output)
    ->nonblocking()
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
$gpio_input = USBGPIOInput::pin(0)->edgeEvents()->alias('echo')
$gpio_output = USBGPIOOutput::pin(1)->alias('trig')

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
