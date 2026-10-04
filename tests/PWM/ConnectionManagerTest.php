<?php

use GeneralPurposeIO\Contracts\PWM\PWMException;
use GeneralPurposeIO\Core\Providers\ScrapyardIOServiceProvider;
use GeneralPurposeIO\PWM\NonePWMConnectionDriver;
use GeneralPurposeIO\PWM\PWMConnectionManager;
use GeneralPurposeIO\PWM\PWMServiceProvider;
use ScrapyardIO\Tests\Fixtures\FakePWMConnectionDriver;
use Voyager\Config\Repository;
use Voyager\Contracts\Core\FrameworkCore;
use Voyager\Vessel\ControlPanel;

/** A container whose gpio.protocols.pwm.default is $default. */
function pwmApp(string $default): ControlPanel
{
    $app = new ControlPanel;
    $app->registerInstance('config', new Repository(['gpio' => ['protocols' => ['pwm' => ['default' => $default]]]]));

    return $app;
}

it('falls back to the none driver, which refuses every chip', function (): void {
    $driver = (new PWMConnectionManager(pwmApp('none')))->driver();

    expect($driver)->toBeInstanceOf(NonePWMConnectionDriver::class)
        ->and(fn () => $driver->connectTo(0))->toThrow(PWMException::class, 'No PWM connection driver is configured');
});

it('hands out an extended driver by its configured name, once', function (): void {
    $manager = new PWMConnectionManager(pwmApp('fake'));
    $manager->extend('fake', fn () => new FakePWMConnectionDriver);

    expect($manager->driver())->toBeInstanceOf(FakePWMConnectionDriver::class)
        ->and($manager->driver())->toBe($manager->driver());
});

it('binds gpio.pwm to one PWMConnectionManager', function (): void {
    $container = pwmApp('none');
    $app = $this->createMock(FrameworkCore::class);

    $app->expects($this->once())->method('registerSingleton')->with(
        'gpio.pwm',
        $this->callback(fn (Closure $make): bool => $make($container) instanceof PWMConnectionManager),
    );
    $app->expects($this->once())->method('alias')->with('gpio.pwm', PWMConnectionManager::class);

    (new PWMServiceProvider($app))->register();
});

it('is aggregated by the ScrapyardIO provider', function (): void {
    $providers = (new ReflectionProperty(ScrapyardIOServiceProvider::class, 'providers'))->getDefaultValue();

    expect($providers)->toContain(PWMServiceProvider::class);
});

/** A driver the manager made, on a container holding a loop and, by binding name, each pool given. */
function pwmOffloadingDriver(array $pools = []): array
{
    $app = pwmApp('fake');
    $loop = testLoop();
    $app->registerInstance(Voyager\Contracts\IOPools\Loop::class, $loop);
    foreach ($pools as $binding => $make) {
        $app->registerInstance($binding, $make($loop));
    }
    $manager = new PWMConnectionManager($app);
    $manager->extend('fake', fn () => new FakePWMConnectionDriver);
    $driver = $manager->driver();
    $driver->connectTo(0)->register();

    return [$driver, $loop];
}

it('hands every driver the loop and the worker pools, looked up when used', function (): void {
    $pool = null;
    [$driver] = pwmOffloadingDriver(['process-workers' => function ($loop) use (&$pool) {
        return $pool = new ScrapyardIO\Tests\Fixtures\InlinePool($loop);
    }]);

    expect($driver->device(0, 0)->via()->setPeriod(20_000_000)->wait())->toBe(20_000_000)
        ->and($pool->submitted)->toBe(1);
});

it('offloads to the thread pool when it is on, and to a pool by name', function (): void {
    $pools = [];
    [$driver] = pwmOffloadingDriver([
        'thread-workers' => function ($loop) use (&$pools) { return $pools['thread'] = new ScrapyardIO\Tests\Fixtures\InlinePool($loop); },
        'process-workers' => function ($loop) use (&$pools) { return $pools['process'] = new ScrapyardIO\Tests\Fixtures\InlinePool($loop); },
    ]);

    $driver->device(0, 0)->via()->setPeriod(20_000_000)->wait();
    $driver->device(0, 0)->via('process')->setPeriod(20_000_000)->wait();
    $driver->device(0, 0)->via('thread')->setPeriod(20_000_000)->wait();

    expect($pools['thread']->submitted)->toBe(2)
        ->and($pools['process']->submitted)->toBe(1);
});

it('says which pool is off, or that none is on', function (): void {
    [$driver] = pwmOffloadingDriver();
    [$process_only] = pwmOffloadingDriver(['process-workers' => fn ($loop) => new ScrapyardIO\Tests\Fixtures\InlinePool($loop)]);

    expect(fn () => $driver->device(0, 0)->via()->setPeriod(20_000_000)->wait())->toThrow(InvalidArgumentException::class, 'none is on')
        ->and(fn () => $driver->device(0, 0)->via('process')->setPeriod(20_000_000)->wait())->toThrow(InvalidArgumentException::class, 'The process pool is off')
        ->and(fn () => $process_only->device(0, 0)->via('thread')->setPeriod(20_000_000)->wait())->toThrow(InvalidArgumentException::class, 'The thread pool is off')
        ->and(fn () => $process_only->device(0, 0)->via('queue')->setPeriod(20_000_000)->wait())->toThrow(InvalidArgumentException::class, 'There is no "queue" pool');
});
