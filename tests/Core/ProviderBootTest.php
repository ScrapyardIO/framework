<?php

use GeneralPurposeIO\Common\GPIOProtocolManager;
use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\Core\IOPools\GPIOResourceDriver;
use GeneralPurposeIO\Core\Providers\ScrapyardIOServiceProvider;
use GeneralPurposeIO\I2C\I2CAdapterManager;
use Voyager\Config\Repository;
use Voyager\IOPools\IOPoolsServiceProvider;
use Voyager\MagicAliases\MagicAlias;
use Voyager\System\Application;

afterEach(function () {
    MagicAlias::clearResolvedInstances();
    MagicAlias::setMagicAliasApplication(null);
});

it('registers the gpio manager and the protocol managers', function () {
    $app = new Application(dirname(__DIR__, 2));
    $app['config'] = new Repository;

    // Protocol-specific facades (UART::extend(), I2C::extend(), ...) called from each
    // child provider's boot() are Voyager magic aliases; they need a root set the same
    // way venusian's own facade tests do (e.g. tests/Cache/CacheSpyMemoTest.php), since
    // a bare Application does not run the RegisterMagicAliases bootstrapper.
    MagicAlias::setMagicAliasApplication($app);

    $app->register(ScrapyardIOServiceProvider::class);
    $app->boot();

    expect($app->make('gpio'))->toBeInstanceOf(GPIOProtocolManager::class)
        ->and($app->bound('gpio.i2c'))->toBeTrue()
        ->and($app->bound('circuit'))->toBeTrue()
        ->and($app['config']->get('gpio.io_pools.enabled'))->toBeTrue()
        ->and($app->make('gpio')->protocol('i2c'))->toBeInstanceOf(I2CAdapterManager::class);
});

it('registers the gpio resource on the dock at boot', function () {
    $app = new Application(dirname(__DIR__, 2));
    $app['config'] = new Repository;

    MagicAlias::setMagicAliasApplication($app);

    $app->register(IOPoolsServiceProvider::class);
    $app->register(ScrapyardIOServiceProvider::class);
    $app->boot();

    expect($app->make('io-pool')->gpio())->toBeInstanceOf(GPIOResourceDriver::class);
});

it('does not register the gpio resource on the dock when gpio.io_pools.enabled is false', function () {
    $app = new Application(dirname(__DIR__, 2));
    $app['config'] = new Repository;

    MagicAlias::setMagicAliasApplication($app);

    $app->register(IOPoolsServiceProvider::class);
    $app->register(ScrapyardIOServiceProvider::class);
    $app['config']->set('gpio.io_pools.enabled', false);
    $app->boot();

    expect($app->make('io-pool')->gpio())->toBeNull();
});

it('throws GPIOException when asking for an unregistered protocol', function () {
    $app = new Application(dirname(__DIR__, 2));
    $app['config'] = new Repository;

    MagicAlias::setMagicAliasApplication($app);

    $app->register(ScrapyardIOServiceProvider::class);
    $app->boot();

    expect(fn () => $app->make('gpio')->protocol('bogus'))
        ->toThrow(GPIOException::class, 'bogus');
});
