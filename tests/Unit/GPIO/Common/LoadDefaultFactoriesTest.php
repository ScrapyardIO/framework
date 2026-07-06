<?php

use GPIO\Common\GPIOConnectionFactory;
use GPIO\Common\LoadDefaultFactories;
use GPIO\Contracts\Common\CarrierFactory;
use GPIO\Contracts\Common\GPIOConnectionFactory as GPIOConnectionFactoryContract;
use GPIO\Digital\DigitalPinConnectionFactory;
use GPIO\Digital\Input\DigitalInputConnectionFactory;
use GPIO\Digital\Output\DigitalOutputConnectionFactory;
use GPIO\I2C\I2CConnectionFactory;
use GPIO\PWM\PWMConnectionFactory;
use GPIO\SPI\SPIConnectionFactory;
use GPIO\UART\UARTConnectionFactory;
use ScrapyardIO\NutsAndBolts\Reflection;

// LoadDefaultFactories scans this framework's own GPIO namespace (see its
// hardcoded dirname(__DIR__) call), so unlike LoadDefaultProtocolManagers it
// never needs a sibling package and can be tested directly against the real
// source tree.

test('it returns an array keyed by protocol name', function () {
    expect(LoadDefaultFactories::run())->toBeArray();
});

test('it discovers every real protocol factory shipped with the framework', function () {
    $result = LoadDefaultFactories::run();

    expect($result)->toEqual([
        'digital-in' => DigitalInputConnectionFactory::class,
        'digital-out' => DigitalOutputConnectionFactory::class,
        'i2c' => I2CConnectionFactory::class,
        'pwm' => PWMConnectionFactory::class,
        'spi' => SPIConnectionFactory::class,
        'uart' => UARTConnectionFactory::class,
    ]);
});

test('it excludes abstract factory classes', function () {
    $result = LoadDefaultFactories::run();

    expect($result)
        ->not->toContain(GPIOConnectionFactory::class)
        ->not->toContain(DigitalPinConnectionFactory::class);
});

test('every discovered factory implements GPIOConnectionFactory and is not abstract', function () {
    $result = LoadDefaultFactories::run();

    expect($result)->not->toBeEmpty();

    foreach ($result as $class) {
        expect(is_subclass_of($class, GPIOConnectionFactoryContract::class))->toBeTrue()
            ->and((new ReflectionClass($class))->isAbstract())->toBeFalse();
    }
});

test('every discovered factory is keyed by its own CarrierFactory attribute value', function () {
    $result = LoadDefaultFactories::run();

    foreach ($result as $protocol => $class) {
        $attribute = Reflection::reflect_class($class, CarrierFactory::class);

        expect($attribute)->not->toBeNull()
            ->and($attribute->newInstance()->protocol)->toBe($protocol);
    }
});
