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

/** A driver the manager made, on a container holding a loop and, by binding name, each pool given. */
function i2cOffloadingDriver(array $pools = []): array
{
    $app = i2cApp('fake');
    $loop = testLoop();
    $app->registerInstance(Voyager\Contracts\IOPools\Loop::class, $loop);
    foreach ($pools as $binding => $make) {
        $app->registerInstance($binding, $make($loop));
    }
    $manager = new I2CConnectionManager($app);
    $manager->extend('fake', fn () => new FakeI2CConnectionDriver);
    $driver = $manager->driver();
    $driver->connectTo(1)->register();

    return [$driver, $loop];
}

it('hands every driver the loop and the worker pools, looked up when used', function (): void {
    $pool = null;
    [$driver] = i2cOffloadingDriver(['process-workers' => function ($loop) use (&$pool) {
        return $pool = new ScrapyardIO\Tests\Fixtures\InlinePool($loop);
    }]);

    expect($driver->device(1, 0x3C)->via()->write([0x00, 0xAF])->wait())->toBe(2)
        ->and($pool->submitted)->toBe(1);
});

it('offloads to the thread pool when it is on, and to a pool by name', function (): void {
    $pools = [];
    [$driver] = i2cOffloadingDriver([
        'thread-workers' => function ($loop) use (&$pools) { return $pools['thread'] = new ScrapyardIO\Tests\Fixtures\InlinePool($loop); },
        'process-workers' => function ($loop) use (&$pools) { return $pools['process'] = new ScrapyardIO\Tests\Fixtures\InlinePool($loop); },
    ]);

    $driver->device(1, 0x3C)->via()->write([0x00, 0xAF])->wait();
    $driver->device(1, 0x3C)->via('process')->write([0x00, 0xAF])->wait();
    $driver->device(1, 0x3C)->via('thread')->write([0x00, 0xAF])->wait();

    expect($pools['thread']->submitted)->toBe(2)
        ->and($pools['process']->submitted)->toBe(1);
});

it('says which pool is off, or that none is on', function (): void {
    [$driver] = i2cOffloadingDriver();
    [$process_only] = i2cOffloadingDriver(['process-workers' => fn ($loop) => new ScrapyardIO\Tests\Fixtures\InlinePool($loop)]);

    expect(fn () => $driver->device(1, 0x3C)->via()->write([0x00, 0xAF])->wait())->toThrow(InvalidArgumentException::class, 'none is on')
        ->and(fn () => $driver->device(1, 0x3C)->via('process')->write([0x00, 0xAF])->wait())->toThrow(InvalidArgumentException::class, 'The process pool is off')
        ->and(fn () => $process_only->device(1, 0x3C)->via('thread')->write([0x00, 0xAF])->wait())->toThrow(InvalidArgumentException::class, 'The thread pool is off')
        ->and(fn () => $process_only->device(1, 0x3C)->via('queue')->write([0x00, 0xAF])->wait())->toThrow(InvalidArgumentException::class, 'There is no "queue" pool');
});
