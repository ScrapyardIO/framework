<?php

use GeneralPurposeIO\Contracts\Core\GPIOLevelException;
use GeneralPurposeIO\Contracts\Digital\DigitalIOException;
use GeneralPurposeIO\Contracts\I2C\I2CException;
use GeneralPurposeIO\Contracts\NutsAndBolts\GPIOException;
use GeneralPurposeIO\Contracts\PWM\PWMException;
use GeneralPurposeIO\Contracts\SPI\SPIException;
use GeneralPurposeIO\Contracts\UART\UARTException;

/*
| One root to catch: every connection-layer exception descends from
| GPIOLevelException, so an app can catch a GPIO fault without naming a
| protocol.
*/

it('roots every protocol exception at GPIOLevelException', function (string $class): void {
    expect(new $class('x'))->toBeInstanceOf(GPIOLevelException::class)
        ->and(new $class('x'))->toBeInstanceOf(RuntimeException::class);
})->with([
    I2CException::class,
    SPIException::class,
    UARTException::class,
    PWMException::class,
    DigitalIOException::class,
    GPIOException::class,
]);

it('gives every protocol a noDriverConfigured factory that points at its own config key', function (string $class, string $key): void {
    $e = $class::noDriverConfigured();

    expect($e)->toBeInstanceOf($class)
        ->and($e->getMessage())->toContain("gpio.protocols.{$key}.default");
})->with([
    [I2CException::class, 'i2c'],
    [SPIException::class, 'spi'],
    [UARTException::class, 'uart'],
    [PWMException::class, 'pwm'],
    [DigitalIOException::class, 'digital-in'],
]);
