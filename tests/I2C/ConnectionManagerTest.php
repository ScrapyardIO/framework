<?php

use GeneralPurposeIO\Contracts\I2C\I2CException;
use GeneralPurposeIO\Core\Providers\ScrapyardIOServiceProvider;
use GeneralPurposeIO\I2C\I2CConnectionManager;
use GeneralPurposeIO\I2C\I2CServiceProvider;
use GeneralPurposeIO\I2C\NoneI2CConnectionDriver;
use ScrapyardIO\Tests\Fixtures\FakeI2CConnectionDriver;
use Voyager\Config\Repository;
use Voyager\Contracts\Core\FrameworkCore;
use Voyager\Vessel\ControlPanel;

/** A container whose gpio.protocols.i2c.default is $default. */
function i2cApp(string $default): ControlPanel
{
    $app = new ControlPanel;
    $app->registerInstance('config', new Repository(['gpio' => ['protocols' => ['i2c' => ['default' => $default]]]]));

    return $app;
}

it('falls back to the none driver, which refuses every bus', function (): void {
    $driver = (new I2CConnectionManager(i2cApp('none')))->driver();

    expect($driver)->toBeInstanceOf(NoneI2CConnectionDriver::class)
        ->and(fn () => $driver->connectTo(1))->toThrow(I2CException::class, 'No I2C connection driver is configured');
});

it('hands out an extended driver by its configured name, once', function (): void {
    $manager = new I2CConnectionManager(i2cApp('fake'));
    $manager->extend('fake', fn () => new FakeI2CConnectionDriver);

    expect($manager->driver())->toBeInstanceOf(FakeI2CConnectionDriver::class)
        ->and($manager->driver())->toBe($manager->driver());
});

it('binds gpio.i2c to one I2CConnectionManager', function (): void {
    $container = i2cApp('none');
    $app = $this->createMock(FrameworkCore::class);

    $app->expects($this->once())->method('registerSingleton')->with(
        'gpio.i2c',
        $this->callback(fn (Closure $make): bool => $make($container) instanceof I2CConnectionManager),
    );
    $app->expects($this->once())->method('alias')->with('gpio.i2c', I2CConnectionManager::class);

    (new I2CServiceProvider($app))->register();
});

it('is aggregated by the ScrapyardIO provider', function (): void {
    $providers = (new ReflectionProperty(ScrapyardIOServiceProvider::class, 'providers'))->getDefaultValue();

    expect($providers)->toContain(I2CServiceProvider::class);
});

it('hands every driver the loop and the work targets, looked up when used', function (): void {
    $app = i2cApp('fake');
    $manager = new I2CConnectionManager($app);
    $manager->extend('fake', fn () => new FakeI2CConnectionDriver);
    $driver = $manager->driver();
    $driver->connectTo(1)->register();

    $loop = new Voyager\IOPools\EventLoop;
    $app->registerInstance(Voyager\Contracts\IOPools\Loop::class, $loop);
    $app->registerInstance('work-targets', new ScrapyardIO\Tests\Fixtures\FakeWorkTargets(new Voyager\IOPools\WorkTargets\SyncTarget($loop)));

    expect($driver->device(1, 0x3C)->via()->write([0x00, 0xAF])->wait())->toBe(2);
});

it('says so when offloading with no work targets bound', function (): void {
    $app = i2cApp('fake');
    $app->registerInstance(Voyager\Contracts\IOPools\Loop::class, new Voyager\IOPools\EventLoop);
    $manager = new I2CConnectionManager($app);
    $manager->extend('fake', fn () => new FakeI2CConnectionDriver);
    $driver = $manager->driver();
    $driver->connectTo(1)->register();

    expect(fn () => $driver->device(1, 0x3C)->via()->write([0x00])->wait())->toThrow(I2CException::class, 'work targets');
});
