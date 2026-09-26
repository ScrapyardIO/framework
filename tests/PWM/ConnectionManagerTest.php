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

it('hands every driver the loop and the work targets, looked up when used', function (): void {
    $app = pwmApp('fake');
    $manager = new PWMConnectionManager($app);
    $manager->extend('fake', fn () => new FakePWMConnectionDriver);
    $driver = $manager->driver();
    $driver->connectTo(0)->register();

    $loop = new Voyager\IOPools\EventLoop;
    $app->registerInstance(Voyager\Contracts\IOPools\Loop::class, $loop);
    $app->registerInstance('work-targets', new ScrapyardIO\Tests\Fixtures\FakeWorkTargets(new Voyager\IOPools\WorkTargets\SyncTarget($loop)));

    expect($driver->device(0, 0)->via()->setPeriod(20_000_000)->wait())->toBe(20_000_000);
});

it('says so when offloading with no work targets bound', function (): void {
    $app = pwmApp('fake');
    $app->registerInstance(Voyager\Contracts\IOPools\Loop::class, new Voyager\IOPools\EventLoop);
    $manager = new PWMConnectionManager($app);
    $manager->extend('fake', fn () => new FakePWMConnectionDriver);
    $driver = $manager->driver();
    $driver->connectTo(0)->register();

    expect(fn () => $driver->device(0, 0)->via()->setPeriod(20_000_000)->wait())->toThrow(PWMException::class, 'work targets');
});
