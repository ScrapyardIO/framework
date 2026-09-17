<?php

use GeneralPurposeIO\Digital\DigitalIOServiceProvider;
use GeneralPurposeIO\Digital\DigitalOConnectionManager;
use GeneralPurposeIO\Digital\NoneDigitalIOConnectionDriver;
use GeneralPurposeIO\I2C\I2CConnectionManager;
use GeneralPurposeIO\I2C\I2CServiceProvider;
use GeneralPurposeIO\I2C\NoneI2CConnectionDriver;
use GeneralPurposeIO\PWM\NonePWMConnectionDriver;
use GeneralPurposeIO\PWM\PWMConnectionManager;
use GeneralPurposeIO\PWM\PWMServiceProvider;
use GeneralPurposeIO\SPI\NoneSPIConnectionDriver;
use GeneralPurposeIO\SPI\SPIConnectionManager;
use GeneralPurposeIO\SPI\SPIServiceProvider;
use GeneralPurposeIO\UART\NoneUARTConnectionDriver;
use GeneralPurposeIO\UART\UARTConnectionManager;
use GeneralPurposeIO\UART\UARTServiceProvider;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakeI2CConnectionDriver;
use Voyager\Config\Repository;
use Voyager\Vessel\Vessel;

/*
| Each protocol provider binds one manager singleton under its gpio.* key and
| aliases the manager class to it, so adapter packages, MagicAliases and
| apps all land on the same instance. The Vessel here is the framework's own
| container with nothing but a config repository in it.
*/

function vesselWith(array $gpio = []): Vessel
{
    $vessel = new Vessel;
    $vessel->instance('config', new Repository(['gpio' => $gpio]));

    return $vessel;
}

$protocols = [
    'I2C' => [I2CServiceProvider::class, 'gpio.i2c', I2CConnectionManager::class, NoneI2CConnectionDriver::class],
    'SPI' => [SPIServiceProvider::class, 'gpio.spi', SPIConnectionManager::class, NoneSPIConnectionDriver::class],
    'UART' => [UARTServiceProvider::class, 'gpio.uart', UARTConnectionManager::class, NoneUARTConnectionDriver::class],
    'PWM' => [PWMServiceProvider::class, 'gpio.pwm', PWMConnectionManager::class, NonePWMConnectionDriver::class],
    'DigitalIO' => [DigitalIOServiceProvider::class, 'gpio.digital', DigitalOConnectionManager::class, NoneDigitalIOConnectionDriver::class],
];

foreach ($protocols as $protocol => [$provider, $key, $manager, $none]) {
    it("{$protocol} provider binds the manager singleton under its key and class", function () use ($provider, $key, $manager): void {
        $vessel = vesselWith();

        (new $provider($vessel))->register();

        expect($vessel->bound($key))->toBeTrue()
            ->and($vessel->make($key))->toBeInstanceOf($manager)
            ->and($vessel->make($manager))->toBe($vessel->make($key))
            ->and($vessel->make($key))->toBe($vessel->make($key));
    });

    it("{$protocol} manager falls back to the None driver with no config at all", function () use ($provider, $key, $none): void {
        $vessel = vesselWith();
        (new $provider($vessel))->register();

        expect($vessel->make($key)->driver())->toBeInstanceOf($none);
    });

    it("{$protocol} provider boots without needing anything else in the container", function () use ($provider): void {
        $vessel = vesselWith();
        $instance = new $provider($vessel);
        $instance->register();

        $instance->boot();

        expect(true)->toBeTrue();
    });
}

it('resolves a driver an adapter package extended the manager with, and honours it as the configured default', function (): void {
    $vessel = vesselWith(['protocols' => ['i2c' => ['default' => 'fake']]]);
    (new I2CServiceProvider($vessel))->register();

    $vessel->make(I2CConnectionManager::class)->extend('fake', fn () => new FakeI2CConnectionDriver);

    expect($vessel->make('gpio.i2c')->driver('fake'))->toBeInstanceOf(FakeI2CConnectionDriver::class)
        ->and($vessel->make('gpio.i2c')->driver())->toBe($vessel->make('gpio.i2c')->driver('fake'));
});
