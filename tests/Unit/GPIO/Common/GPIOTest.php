<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\FakeCarrierDriverManager;
use GPIO\Common\GPIO;
use GPIO\Common\GPIOCarriers;
use GPIO\Contracts\Common\GPIOException;
use GPIO\Contracts\Digital\DigitalInputConnectionFactory as DigitalInputConnectionFactoryContract;
use GPIO\Contracts\Digital\DigitalOutputConnectionFactory as DigitalOutputConnectionFactoryContract;
use GPIO\Contracts\I2C\I2CConnectionFactory as I2CConnectionFactoryContract;
use GPIO\Contracts\SPI\SPIConnectionFactory as SPIConnectionFactoryContract;
use GPIO\Contracts\UART\UARTConnectionFactory as UARTConnectionFactoryContract;
use GPIO\Digital\Input\DigitalInputConnectionFactory;
use GPIO\Digital\Output\DigitalOutputConnectionFactory;
use GPIO\I2C\I2CConnectionFactory;
use GPIO\SPI\SPIConnectionFactory;
use GPIO\UART\UARTConnectionFactory;

/**
 * GPIO and GPIOCarriers are singletons, so each test resets them and boots
 * GPIOCarriers with a fake carrier instead of the real default protocol
 * managers (LoadDefaultProtocolManagers::run()). Those defaults are
 * discovered from sibling driver packages (e.g. microscrap/usb-drivers)
 * that this framework package doesn't itself depend on, so relying on them
 * here would make this unit test pass or fail based on what happens to be
 * composer-installed alongside it rather than on GPIO's own factory-wiring
 * logic, which is what "creating factories" is actually about.
 */
function resetGpioSingletons(): void
{
    foreach ([GPIO::class, GPIOCarriers::class] as $class) {
        $property = (new ReflectionClass($class))->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }
}

beforeEach(function () {
    resetGpioSingletons();
    GPIOCarriers::boot(['fake' => FakeCarrierDriverManager::class]);
});

afterEach(function () {
    resetGpioSingletons();
});

test('it creates a DigitalIn Factory', function () {
    $result = GPIO::digitalIn('fake');

    expect($result)
        ->toBeInstanceOf(DigitalInputConnectionFactory::class)
        ->toBeInstanceOf(DigitalInputConnectionFactoryContract::class);
});

test('it creates a DigitalOut Factory', function () {
    $result = GPIO::digitalOut('fake');

    expect($result)
        ->toBeInstanceOf(DigitalOutputConnectionFactory::class)
        ->toBeInstanceOf(DigitalOutputConnectionFactoryContract::class);
});

test('it creates an I2C Factory', function () {
    $result = GPIO::i2c('fake');

    expect($result)
        ->toBeInstanceOf(I2CConnectionFactory::class)
        ->toBeInstanceOf(I2CConnectionFactoryContract::class);
});

test('it creates a SPI Factory', function () {
    $result = GPIO::spi('fake');

    expect($result)
        ->toBeInstanceOf(SPIConnectionFactory::class)
        ->toBeInstanceOf(SPIConnectionFactoryContract::class);
});

test('it creates a UART Factory', function () {
    $result = GPIO::uart('fake');

    expect($result)
        ->toBeInstanceOf(UARTConnectionFactory::class)
        ->toBeInstanceOf(UARTConnectionFactoryContract::class);
});

test('creating a PWM Factory throws when the carrier does not support it', function () {
    GPIO::pwm('fake');
})->throws(GPIOException::class, 'fake does not support pwm.');

test('creating a Factory for an unregistered carrier throws', function () {
    GPIO::digitalIn('not-a-real-carrier');
})->throws(GPIOException::class, 'GPIO\Common\GPIOCarriers does not implement a static not-a-real-carrier method.');

test('requesting an unknown protocol throws', function () {
    GPIO::notAProtocol('fake');
})->throws(GPIOException::class, 'GPIO\Common\GPIO does not implement a static notAProtocol method.');
