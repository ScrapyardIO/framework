<?php

use GeneralPurposeIO\Contracts\SPI\SPIException;
use GeneralPurposeIO\Core\Providers\ScrapyardIOServiceProvider;
use GeneralPurposeIO\SPI\NoneSPIConnectionDriver;
use GeneralPurposeIO\SPI\SPIConnectionManager;
use GeneralPurposeIO\SPI\SPIServiceProvider;
use ScrapyardIO\Tests\Fixtures\FakeSPIConnectionDriver;
use Voyager\Config\Repository;
use Voyager\Contracts\Core\FrameworkCore;
use Voyager\Vessel\ControlPanel;

/** A container whose gpio.protocols.spi.default is $default. */
function spiApp(string $default): ControlPanel
{
    $app = new ControlPanel;
    $app->registerInstance('config', new Repository(['gpio' => ['protocols' => ['spi' => ['default' => $default]]]]));

    return $app;
}

it('falls back to the none driver, which refuses every bus', function () {
    $driver = (new SPIConnectionManager(spiApp('none')))->driver();

    expect($driver)->toBeInstanceOf(NoneSPIConnectionDriver::class)
        ->and(fn () => $driver->connectTo(0))->toThrow(SPIException::class, 'No SPI connection driver is configured');
});

it('hands out an extended driver by its configured name, once', function () {
    $manager = new SPIConnectionManager(spiApp('fake'));
    $manager->extend('fake', fn () => new FakeSPIConnectionDriver);

    expect($manager->driver())->toBeInstanceOf(FakeSPIConnectionDriver::class)
        ->and($manager->driver())->toBe($manager->driver());
});

it('binds gpio.spi to one SPIConnectionManager', function () {
    $container = spiApp('none');
    $app = $this->createMock(FrameworkCore::class);

    $app->expects($this->once())->method('registerSingleton')->with(
        'gpio.spi',
        $this->callback(fn (Closure $make): bool => $make($container) instanceof SPIConnectionManager),
    );
    $app->expects($this->once())->method('alias')->with('gpio.spi', SPIConnectionManager::class);

    (new SPIServiceProvider($app))->register();
});

it('is aggregated by the ScrapyardIO provider', function () {
    $providers = (new ReflectionProperty(ScrapyardIOServiceProvider::class, 'providers'))->getDefaultValue();

    expect($providers)->toContain(SPIServiceProvider::class);
});

it('hands every driver the loop and the work targets, looked up when used', function () {
    $app = spiApp('fake');
    $manager = new SPIConnectionManager($app);
    $manager->extend('fake', fn () => new FakeSPIConnectionDriver);
    $driver = $manager->driver();
    $driver->connectTo(1)->register();

    $loop = new Voyager\IOPools\EventLoop;
    $app->registerInstance(Voyager\Contracts\IOPools\Loop::class, $loop);
    $app->registerInstance('work-targets', new ScrapyardIO\Tests\Fixtures\FakeWorkTargets(new Voyager\IOPools\WorkTargets\SyncTarget($loop)));

    expect($driver->device(1, 0)->via()->write([0x00, 0xAF])->wait())->toBe(2);
});

it('says so when offloading with no work targets bound', function () {
    $app = spiApp('fake');
    $app->registerInstance(Voyager\Contracts\IOPools\Loop::class, new Voyager\IOPools\EventLoop);
    $manager = new SPIConnectionManager($app);
    $manager->extend('fake', fn () => new FakeSPIConnectionDriver);
    $driver = $manager->driver();
    $driver->connectTo(1)->register();

    expect(fn () => $driver->device(1, 0)->via()->write([0x00])->wait())->toThrow(SPIException::class, 'work targets');
});
