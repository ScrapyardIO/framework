<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital\FakeDigitalCarrierDriverManager;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\FakeCarrierDriverManager;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\I2C\FakeI2CCarrierDriverManager;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\I2C\FakeI2CDriverAdapter;
use GPIO\Common\GPIO;
use GPIO\Common\GPIOCarriers;
use GPIO\Contracts\Common\CarrierFactory;
use GPIO\Contracts\Digital\LineBias;
use GPIO\Contracts\I2C\I2CException;
use GPIO\Digital\Input\DigitalInputConnectionFactory;
use GPIO\Digital\Output\DigitalOutputConnectionFactory;
use GPIO\I2C\I2C;
use GPIO\I2C\I2CConnectionFactory;
use ScrapyardIO\NutsAndBolts\Reflection;

// driver_adapter is protected with no accessor, so reflection is needed to
// reach into the factory and assert what the underlying fake driver
// actually recorded.
function i2cFactoryDriverAdapter(I2CConnectionFactory $factory): FakeI2CDriverAdapter
{
    $property = new ReflectionProperty($factory, 'driver_adapter');
    $property->setAccessible(true);

    return $property->getValue($factory);
}

function resetGpioAndCarriersSingletons(): void
{
    foreach ([GPIO::class, GPIOCarriers::class] as $class) {
        $property = (new ReflectionClass($class))->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }
}

beforeEach(function () {
    resetGpioAndCarriersSingletons();

    // Also boots the 'fake-digital' carrier, purely so the "additional
    // digital pins" test below can build a DigitalOutputConnectionFactory
    // to bundle onto the I2C factory without touching a real hardware
    // carrier - I2CConnectionFactory itself only ever talks to 'fake-i2c'.
    GPIOCarriers::boot([
        'fake-i2c' => FakeI2CCarrierDriverManager::class,
        'fake-digital' => FakeDigitalCarrierDriverManager::class,
    ]);
});

afterEach(function () {
    resetGpioAndCarriersSingletons();
});

test('it is registered under the i2c protocol', function () {
    $attribute = Reflection::reflect_class(I2CConnectionFactory::class, CarrierFactory::class);

    expect($attribute)->not->toBeNull()
        ->and($attribute->newInstance()->protocol)->toBe('i2c');
});

test('device() and slave() are fluent and set the expected properties', function () {
    $factory = new I2CConnectionFactory('fake-i2c');

    $result = $factory->device('/dev/i2c-1')->slave(0x42);

    expect($result)->toBe($factory)
        ->and($factory->master_host_device)->toBe('/dev/i2c-1')
        ->and($factory->slave_address)->toBe(0x42);
});

test('slave_address and master_host_device default to null', function () {
    $factory = new I2CConnectionFactory('fake-i2c');

    expect($factory->slave_address)->toBeNull()
        ->and($factory->master_host_device)->toBeNull();
});

test('digitalPins() is fluent and sets the gpio_chip property', function () {
    $factory = new I2CConnectionFactory('fake-i2c');

    $result = $factory->digitalPins(0);

    expect($result)->toBe($factory)
        ->and($factory->gpio_chip)->toBe(0)
        ->and($factory->digital_pins)->toBe([]);
});

test('slave() accepts every address in the valid 0x08-0x77 range', function () {
    $factory = new I2CConnectionFactory('fake-i2c');

    $factory->slave(0x08);
    expect($factory->slave_address)->toBe(0x08);

    $factory->slave(0x77);
    expect($factory->slave_address)->toBe(0x77);
});

test('slave() throws for an address below 0x08', function () {
    (new I2CConnectionFactory('fake-i2c'))->slave(0x07);
})->throws(I2CException::class, 'Only valid address between 0x08 and 0x77 allowed. Requested: [7].');

test('slave() throws for an address above 0x77', function () {
    (new I2CConnectionFactory('fake-i2c'))->slave(0x78);
})->throws(I2CException::class, 'Only valid address between 0x08 and 0x77 allowed. Requested: [120].');

test('slave() leaves slave_address unset when it throws', function () {
    $factory = new I2CConnectionFactory('fake-i2c');

    try {
        $factory->slave(0x99);
    } catch (I2CException) {
        //
    }

    expect($factory->slave_address)->toBeNull();
});

test('create() throws when no master device has been set', function () {
    (new I2CConnectionFactory('fake-i2c'))->slave(0x42)->create();
})->throws(I2CException::class, 'I2C Master device is missing.');

test('create() throws when no slave address has been set', function () {
    (new I2CConnectionFactory('fake-i2c'))->device('/dev/i2c-1')->create();
})->throws(I2CException::class, 'Slave address is missing.');

test('create() forwards the configured master device, slave address and gpio chip to the driver adapter', function () {
    $factory = (new I2CConnectionFactory('fake-i2c'))
        ->device('/dev/i2c-1')
        ->slave(0x42)
        ->digitalPins(0);

    $driver = i2cFactoryDriverAdapter($factory);

    $result = $factory->create();

    expect($result)->toBeInstanceOf(I2C::class)
        ->and($driver->buildConnectionCalls)->toBe([
            [
                'master' => '/dev/i2c-1',
                'slave' => 0x42,
                'gpio_chip' => 0,
                'digital_pins' => [],
            ],
        ]);
});

test('create() forwards any additional digital pins bundled onto the factory', function () {
    $factory = (new I2CConnectionFactory('fake-i2c'))
        ->device('/dev/i2c-1')
        ->slave(0x42);

    $resetPin = (new DigitalOutputConnectionFactory('fake-digital'));
    $factory->digital_pins[] = $resetPin;

    $driver = i2cFactoryDriverAdapter($factory);

    $factory->create();

    expect($driver->buildConnectionCalls[0]['digital_pins'])->toBe([$resetPin]);
});

// digitalIn()/digitalOut() come from the DigitalPinsOnBus trait and go
// through the real GPIO facade (GPIO::digitalIn()/digitalOut()), so these
// need the real default factories booted, plus a fake carrier that itself
// supports digital-in/digital-out alongside i2c (unlike FakeI2CCarrierDriverManager,
// which only supports i2c).

test('digitalIn() appends a configured DigitalInputConnectionFactory to digital_pins and is fluent', function () {
    // beforeEach already booted GPIOCarriers with fake-i2c/fake-digital, and
    // boot() is a singleton that ignores later calls, so it needs resetting
    // before booting the 'fake' carrier that supports both i2c and digital.
    resetGpioAndCarriersSingletons();
    GPIOCarriers::boot(['fake' => FakeCarrierDriverManager::class]);
    $factory = new I2CConnectionFactory('fake');

    $result = $factory->digitalIn(17, 'clock', [true, false], 500, LineBias::PULL_UP);

    expect($result)->toBe($factory)
        ->and($factory->digital_pins)->toHaveCount(1);

    $pin = $factory->digital_pins[0];
    expect($pin)->toBeInstanceOf(DigitalInputConnectionFactory::class)
        ->and($pin->pin)->toBe(17)
        ->and($pin->name)->toBe('clock')
        ->and($pin->rising_events)->toBeTrue()
        ->and($pin->falling_events)->toBeFalse()
        ->and($pin->timeout_ms)->toBe(500)
        ->and($pin->line_bias)->toBe(LineBias::PULL_UP);
});

test('digitalOut() appends a configured DigitalOutputConnectionFactory to digital_pins and is fluent', function () {
    resetGpioAndCarriersSingletons();
    GPIOCarriers::boot(['fake' => FakeCarrierDriverManager::class]);
    $factory = new I2CConnectionFactory('fake');

    $result = $factory->digitalOut(27, 'reset', true);

    expect($result)->toBe($factory)
        ->and($factory->digital_pins)->toHaveCount(1);

    $pin = $factory->digital_pins[0];
    expect($pin)->toBeInstanceOf(DigitalOutputConnectionFactory::class)
        ->and($pin->pin)->toBe(27)
        ->and($pin->name)->toBe('reset')
        ->and($pin->default_state)->toBeTrue();
});

test('digitalIn() and digitalOut() can be combined and both land in digital_pins in call order', function () {
    resetGpioAndCarriersSingletons();
    GPIOCarriers::boot(['fake' => FakeCarrierDriverManager::class]);
    $factory = (new I2CConnectionFactory('fake'))
        ->digitalIn(17, 'clock', [false, false], 1000, LineBias::AS_IS)
        ->digitalOut(27, 'reset', false);

    expect($factory->digital_pins)->toHaveCount(2)
        ->and($factory->digital_pins[0])->toBeInstanceOf(DigitalInputConnectionFactory::class)
        ->and($factory->digital_pins[1])->toBeInstanceOf(DigitalOutputConnectionFactory::class);
});
