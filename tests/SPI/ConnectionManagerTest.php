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

/** A driver the manager made, on a container holding a loop and, by binding name, each pool given. */
function spiOffloadingDriver(array $pools = []): array
{
    $app = spiApp('fake');
    $loop = testLoop();
    $app->registerInstance(Voyager\Contracts\IOPools\Loop::class, $loop);
    foreach ($pools as $binding => $make) {
        $app->registerInstance($binding, $make($loop));
    }
    $manager = new SPIConnectionManager($app);
    $manager->extend('fake', fn () => new FakeSPIConnectionDriver);
    $driver = $manager->driver();
    $driver->connectTo(1)->register();

    return [$driver, $loop];
}

it('hands every driver the loop and the worker pools, looked up when used', function (): void {
    $pool = null;
    [$driver] = spiOffloadingDriver(['process-workers' => function ($loop) use (&$pool) {
        return $pool = new ScrapyardIO\Tests\Fixtures\InlinePool($loop);
    }]);

    expect($driver->device(1, 0)->via()->write([0x00, 0xAF])->wait())->toBe(2)
        ->and($pool->submitted)->toBe(1);
});

it('offloads to the thread pool when it is on, and to a pool by name', function (): void {
    $pools = [];
    [$driver] = spiOffloadingDriver([
        'thread-workers' => function ($loop) use (&$pools) { return $pools['thread'] = new ScrapyardIO\Tests\Fixtures\InlinePool($loop); },
        'process-workers' => function ($loop) use (&$pools) { return $pools['process'] = new ScrapyardIO\Tests\Fixtures\InlinePool($loop); },
    ]);

    $driver->device(1, 0)->via()->write([0x00, 0xAF])->wait();
    $driver->device(1, 0)->via('process')->write([0x00, 0xAF])->wait();
    $driver->device(1, 0)->via('thread')->write([0x00, 0xAF])->wait();

    expect($pools['thread']->submitted)->toBe(2)
        ->and($pools['process']->submitted)->toBe(1);
});

it('says which pool is off, or that none is on', function (): void {
    [$driver] = spiOffloadingDriver();
    [$process_only] = spiOffloadingDriver(['process-workers' => fn ($loop) => new ScrapyardIO\Tests\Fixtures\InlinePool($loop)]);

    expect(fn () => $driver->device(1, 0)->via()->write([0x00, 0xAF])->wait())->toThrow(InvalidArgumentException::class, 'none is on')
        ->and(fn () => $driver->device(1, 0)->via('process')->write([0x00, 0xAF])->wait())->toThrow(InvalidArgumentException::class, 'The process pool is off')
        ->and(fn () => $process_only->device(1, 0)->via('thread')->write([0x00, 0xAF])->wait())->toThrow(InvalidArgumentException::class, 'The thread pool is off')
        ->and(fn () => $process_only->device(1, 0)->via('queue')->write([0x00, 0xAF])->wait())->toThrow(InvalidArgumentException::class, 'There is no "queue" pool');
});
