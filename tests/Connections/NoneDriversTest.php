<?php

use GeneralPurposeIO\Contracts\Core\GPIOLevelException;
use GeneralPurposeIO\Contracts\Digital\DigitalIOException;
use GeneralPurposeIO\Contracts\I2C\I2CException;
use GeneralPurposeIO\Contracts\PWM\PWMException;
use GeneralPurposeIO\Contracts\SPI\SPIException;
use GeneralPurposeIO\Contracts\UART\UARTException;
use GeneralPurposeIO\Digital\DigitalIOConnectionDriver;
use GeneralPurposeIO\Digital\NoneDigitalIOConnectionDriver;
use GeneralPurposeIO\I2C\I2CConnectionDriver;
use GeneralPurposeIO\I2C\NoneI2CConnectionDriver;
use GeneralPurposeIO\PWM\NonePWMConnectionDriver;
use GeneralPurposeIO\PWM\PWMConnectionDriver;
use GeneralPurposeIO\SPI\NoneSPIConnectionDriver;
use GeneralPurposeIO\SPI\SPIConnectionDriver;
use GeneralPurposeIO\UART\NoneUARTConnectionDriver;
use GeneralPurposeIO\UART\UARTConnectionDriver;

/*
| With no adapter package installed every manager creates its None driver.
| That driver must fail at the first open with the config key to set, never
| return null from a creator or a transport that goes nowhere.
*/

$none = [
    'I2C' => [
        NoneI2CConnectionDriver::class, I2CConnectionDriver::class, I2CException::class, 'gpio.protocols.i2c.default',
        1, fn ($d) => $d->device(1, 0x3C),
    ],
    'SPI' => [
        NoneSPIConnectionDriver::class, SPIConnectionDriver::class, SPIException::class, 'gpio.protocols.spi.default',
        0, fn ($d) => $d->device(0, 0),
    ],
    'UART' => [
        NoneUARTConnectionDriver::class, UARTConnectionDriver::class, UARTException::class, 'gpio.protocols.uart.default',
        '/dev/ttyAMA0', fn ($d) => $d->device('/dev/ttyAMA0'),
    ],
    'PWM' => [
        NonePWMConnectionDriver::class, PWMConnectionDriver::class, PWMException::class, 'gpio.protocols.pwm.default',
        0, fn ($d) => $d->device(0, 0),
    ],
    'DigitalIO output' => [
        NoneDigitalIOConnectionDriver::class, DigitalIOConnectionDriver::class, DigitalIOException::class, 'gpio.protocols.digital-in.default',
        0, fn ($d) => $d->output(0, 17),
    ],
    'DigitalIO input' => [
        NoneDigitalIOConnectionDriver::class, DigitalIOConnectionDriver::class, DigitalIOException::class, 'gpio.protocols.digital-in.default',
        0, fn ($d) => $d->input(0, 17),
    ],
];

foreach ($none as $protocol => [$class, $base, $exception, $config_key, $device, $open]) {
    it("{$protocol} None driver is a real driver of its protocol", function () use ($class, $base): void {
        expect(new $class)->toBeInstanceOf($base);
    });

    it("{$protocol} None driver refuses to connect and names the config key to set", function () use ($class, $exception, $config_key, $device): void {
        $driver = new $class;

        expect(fn () => $driver->connectTo($device))->toThrow($exception, 'No ')
            ->and(fn () => $driver->connectTo($device))->toThrow($exception, $config_key);
    });

    it("{$protocol} None driver's refusal is catchable as the framework root exception", function () use ($class, $device): void {
        $driver = new $class;

        expect(fn () => $driver->connectTo($device))->toThrow(GPIOLevelException::class);
    });

    it("{$protocol} None driver returns null for a device nothing could have connected", function () use ($class, $open): void {
        expect($open(new $class))->toBeNull();
    });

    it("{$protocol} None driver refuses a transport even when a handle is forced in through register()", function () use ($class, $exception, $device, $open): void {
        $driver = new $class;
        $driver->register($device, 'stray-handle');

        expect(fn () => $open($driver))->toThrow($exception, 'No ');
    });

    it("{$protocol} None driver never names an adapter package the framework cannot know about", function () use ($class, $device): void {
        $driver = new $class;

        try {
            $driver->connectTo($device);
        } catch (GPIOLevelException $e) {
            expect($e->getMessage())->not->toContain('native')
                ->and($e->getMessage())->not->toContain('usb')
                ->and($e->getMessage())->not->toContain('microscrap');

            return;
        }

        $this->fail('connectTo() did not throw.');
    });
}
