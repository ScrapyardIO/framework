<?php

use GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException;
use GeneralPurposeIO\Contracts\NutsAndBolts\GPIOException;
use GeneralPurposeIO\Contracts\Core\GPIOLevelException;
use GeneralPurposeIO\Contracts\Digital\DigitalIOException;
use GeneralPurposeIO\Contracts\I2C\I2CException;
use GeneralPurposeIO\Contracts\PWM\PWMException;
use GeneralPurposeIO\Contracts\SPI\SPIException;
use GeneralPurposeIO\Contracts\UART\UARTException;

it('roots every exception in GPIOLevelException', function (string $class) {
    expect(new $class('x'))->toBeInstanceOf(GPIOLevelException::class)
        ->and(new $class('x'))->toBeInstanceOf(RuntimeException::class);
})->with([
    GPIOException::class, DigitalIOException::class, I2CException::class, SPIException::class,
    UARTException::class, PWMException::class, CircuitException::class,
]);
