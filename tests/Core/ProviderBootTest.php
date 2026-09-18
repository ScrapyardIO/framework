<?php

use GeneralPurposeIO\Core\IOPools\GPIOResourceDriver;
use GeneralPurposeIO\I2C\I2CConnectionManager;

/* bootedApplication() lives in tests/Pest.php so a filtered run still has it. */

it('merges the gpio config and binds every protocol manager', function (): void {
    $app = bootedApplication();

    expect($app['config']->get('gpio.io_pools.enabled'))->toBeTrue()
        ->and($app['config']->get('gpio.protocols.i2c.default'))->toBe('none')
        ->and($app->make('gpio.i2c'))->toBeInstanceOf(I2CConnectionManager::class)
        ->and($app->bound('gpio.spi'))->toBeTrue()
        ->and($app->bound('gpio.uart'))->toBeTrue()
        ->and($app->bound('gpio.pwm'))->toBeTrue()
        ->and($app->bound('gpio.digital'))->toBeTrue();
});

it('registers the gpio resource on the dock at boot', function (): void {
    $app = bootedApplication();

    expect($app->make('io-pool')->gpio())->toBeInstanceOf(GPIOResourceDriver::class);
});

it('does not register the gpio resource when gpio.io_pools.enabled is false', function (): void {
    $app = bootedApplication(['io_pools' => ['enabled' => false]]);

    expect($app->make('io-pool')->gpio())->toBeNull();
});

it('passes the configured defer budget to the resource', function (): void {
    $app = bootedApplication(['io_pools' => ['enabled' => true, 'defer_per_tick' => 0]]);
})->throws(GeneralPurposeIO\Contracts\NutsAndBolts\GPIOException::class, 'defer_per_tick');

it('binds the same resource in the container as gpio, under its contract, and not at all when disabled', function (): void {
    $app = bootedApplication();

    expect($app->make('gpio'))->toBe($app->make('io-pool')->gpio())
        ->and($app->make(GeneralPurposeIO\Contracts\Core\GPIOResourceDriver::class))->toBe($app->make('gpio'))
        ->and(bootedApplication(['io_pools' => ['enabled' => false]])->bound('gpio'))->toBeFalse();
});

it('reaches the resource and every manager through the MagicAliases the manifest advertises', function (): void {
    $app = bootedApplication();
    Voyager\MagicAliases\MagicAlias::setMagicAliasApplication($app);

    $aliases = json_decode(file_get_contents(dirname(__DIR__, 2).'/composer.json'), true)['extra']['venusian']['aliases'];

    foreach ($aliases as $short => $class) {
        expect(class_exists($class))->toBeTrue("alias {$short} points at a missing class {$class}")
            ->and(is_subclass_of($class, Voyager\MagicAliases\MagicAlias::class))->toBeTrue();
    }

    expect($aliases)->toHaveKeys(['GPIO', 'DigitalIO', 'I2C', 'SPI', 'UART', 'PWM'])
        ->and(GeneralPurposeIO\Core\MagicAliases\GPIO::inFlight('nothing'))->toBeNull()
        ->and(GeneralPurposeIO\Core\MagicAliases\GPIO::defer('x', fn () => 1))->toBeInstanceOf(Voyager\IOPools\Presumption::class)
        ->and(GeneralPurposeIO\I2C\I2C::driver())->toBeInstanceOf(GeneralPurposeIO\I2C\NoneI2CConnectionDriver::class)
        ->and(GeneralPurposeIO\Digital\DigitalIO::driver())->toBeInstanceOf(GeneralPurposeIO\Digital\NoneDigitalIOConnectionDriver::class)
        ->and(GeneralPurposeIO\PWM\PWM::driver())->toBeInstanceOf(GeneralPurposeIO\PWM\NonePWMConnectionDriver::class);

    Voyager\MagicAliases\MagicAlias::clearResolvedInstances();
    Voyager\MagicAliases\MagicAlias::setMagicAliasApplication(null);
});
