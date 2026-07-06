<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\UART\FakeUARTCarrierDriverManager;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\UART\FakeUARTDriverAdapter;
use GPIO\Common\GPIO;
use GPIO\Common\GPIOCarriers;
use GPIO\Contracts\Common\CarrierFactory;
use GPIO\Contracts\UART\DataBits;
use GPIO\Contracts\UART\FlowControl;
use GPIO\Contracts\UART\Parity;
use GPIO\Contracts\UART\StopBits;
use GPIO\Contracts\UART\UARTException;
use GPIO\UART\UART;
use GPIO\UART\UARTConnectionFactory;
use ScrapyardIO\NutsAndBolts\Reflection;

// driver_adapter is protected with no accessor, so reflection is needed to
// reach into the factory and assert what the underlying fake driver
// actually recorded.
function uartFactoryDriverAdapter(UARTConnectionFactory $factory): FakeUARTDriverAdapter
{
    $property = new ReflectionProperty($factory, 'driver_adapter');
    $property->setAccessible(true);

    return $property->getValue($factory);
}

function resetGpioAndCarriersSingletonsForUart(): void
{
    foreach ([GPIO::class, GPIOCarriers::class] as $class) {
        $property = (new ReflectionClass($class))->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }
}

beforeEach(function () {
    resetGpioAndCarriersSingletonsForUart();

    GPIOCarriers::boot(['fake-uart' => FakeUARTCarrierDriverManager::class]);
});

afterEach(function () {
    resetGpioAndCarriersSingletonsForUart();
});

test('it is registered under the uart protocol', function () {
    $attribute = Reflection::reflect_class(UARTConnectionFactory::class, CarrierFactory::class);

    expect($attribute)->not->toBeNull()
        ->and($attribute->newInstance()->protocol)->toBe('uart');
});

test('its defaults match a plain, unconfigured UART connection', function () {
    $factory = new UARTConnectionFactory('fake-uart');

    expect($factory->master_port_device)->toBeNull()
        ->and($factory->baud_rate)->toBe(9600)
        ->and($factory->parity)->toBe(Parity::NONE)
        ->and($factory->stop_bits)->toBe(StopBits::ONE)
        ->and($factory->data_bits)->toBe(DataBits::EIGHT)
        ->and($factory->flow_control)->toBe(FlowControl::NONE);
});

test('device(), baud(), parity(), dataBits(), stopBits() and flowControl() are fluent and set the expected properties', function () {
    $factory = new UARTConnectionFactory('fake-uart');

    $result = $factory
        ->device('/dev/ttyUSB0')
        ->baud(115_200)
        ->parity(Parity::EVEN)
        ->dataBits(DataBits::SEVEN)
        ->stopBits(StopBits::TWO)
        ->flowControl(FlowControl::HARDWARE);

    expect($result)->toBe($factory)
        ->and($factory->master_port_device)->toBe('/dev/ttyUSB0')
        ->and($factory->baud_rate)->toBe(115_200)
        ->and($factory->parity)->toBe(Parity::EVEN)
        ->and($factory->data_bits)->toBe(DataBits::SEVEN)
        ->and($factory->stop_bits)->toBe(StopBits::TWO)
        ->and($factory->flow_control)->toBe(FlowControl::HARDWARE);
});

test('create() throws when no master device has been set', function () {
    (new UARTConnectionFactory('fake-uart'))->create();
})->throws(UARTException::class, 'UART Port device is missing.');

test('create() does not require baud rate, parity, data bits, stop bits or flow control to be explicitly set', function () {
    $result = (new UARTConnectionFactory('fake-uart'))
        ->device('/dev/ttyUSB0')
        ->create();

    expect($result)->toBeInstanceOf(UART::class);
});

test('create() forwards every configured option to the driver adapter', function () {
    $factory = (new UARTConnectionFactory('fake-uart'))
        ->device('/dev/ttyUSB0')
        ->baud(57_600)
        ->parity(Parity::ODD)
        ->dataBits(DataBits::SIX)
        ->stopBits(StopBits::TWO)
        ->flowControl(FlowControl::SOFTWARE);

    $driver = uartFactoryDriverAdapter($factory);

    $result = $factory->create();

    expect($result)->toBeInstanceOf(UART::class)
        ->and($driver->buildConnectionCalls)->toBe([
            [
                'port_device' => '/dev/ttyUSB0',
                'baud_rate' => 57_600,
                'parity' => Parity::ODD,
                'stop_bits' => StopBits::TWO,
                'data_bits' => DataBits::SIX,
                'flow_control' => FlowControl::SOFTWARE,
            ],
        ]);
});
