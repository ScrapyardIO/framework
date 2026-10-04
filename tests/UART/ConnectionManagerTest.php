<?php

use GeneralPurposeIO\Contracts\Core\GPIOLevelException;
use GeneralPurposeIO\Contracts\UART\UARTException;
use GeneralPurposeIO\Core\Providers\ScrapyardIOServiceProvider;
use GeneralPurposeIO\UART\NoneUARTConnectionDriver;
use GeneralPurposeIO\UART\UARTConnectionManager;
use GeneralPurposeIO\UART\UARTServiceProvider;
use ScrapyardIO\Tests\Fixtures\FakeUARTConnectionDriver;
use Voyager\Config\Repository;
use Voyager\Contracts\Core\FrameworkCore;
use Voyager\Vessel\ControlPanel;

/** A container whose gpio.protocols.uart.default is $default. */
function uartApp(string $default): ControlPanel
{
    $app = new ControlPanel;
    $app->registerInstance('config', new Repository(['gpio' => ['protocols' => ['uart' => ['default' => $default]]]]));

    return $app;
}

it('falls back to the none driver, which refuses every port', function (): void {
    $driver = (new UARTConnectionManager(uartApp('none')))->driver();

    expect($driver)->toBeInstanceOf(NoneUARTConnectionDriver::class)
        ->and(fn () => $driver->connectTo('/dev/ttyAMA0'))->toThrow(UARTException::class, 'No UART connection driver is configured');
});

it('hands out an extended driver by its configured name, once', function (): void {
    $manager = new UARTConnectionManager(uartApp('fake'));
    $manager->extend('fake', fn () => new FakeUARTConnectionDriver);

    expect($manager->driver())->toBeInstanceOf(FakeUARTConnectionDriver::class)
        ->and($manager->driver())->toBe($manager->driver());
});

it('binds gpio.uart to one UARTConnectionManager', function (): void {
    $container = uartApp('none');
    $app = $this->createMock(FrameworkCore::class);

    $app->expects($this->once())->method('registerSingleton')->with(
        'gpio.uart',
        $this->callback(fn (Closure $make): bool => $make($container) instanceof UARTConnectionManager),
    );
    $app->expects($this->once())->method('alias')->with('gpio.uart', UARTConnectionManager::class);

    (new UARTServiceProvider($app))->register();
});

it('is aggregated by the ScrapyardIO provider', function (): void {
    $providers = (new ReflectionProperty(ScrapyardIOServiceProvider::class, 'providers'))->getDefaultValue();

    expect($providers)->toContain(UARTServiceProvider::class);
});

it('names both engines when an FTDI bridge is busy', function (): void {
    expect(GPIOLevelException::ftdiEngineBusy('ft232h', 'mpsse', 'uart')->getMessage())
        ->toBe('FTDI device ft232h is open for mpsse: its one engine runs mpsse or uart, not both. Disconnect it first.');
});

it('hands every driver the loop, looked up when used', function (): void {
    $app = uartApp('fake');
    $manager = new UARTConnectionManager($app);
    $manager->extend('fake', fn () => new FakeUARTConnectionDriver);
    $driver = $manager->driver();
    $driver->connectTo('bench')->register();
    $port = $driver->device('bench');

    expect(fn () => $port->watch())->toThrow(UARTException::class, 'watch() needs an event loop.');

    $app->registerInstance(Voyager\Contracts\IOPools\Loop::class, testLoop());
    $port->watch();

    expect(fn () => $port->unwatch())->not->toThrow(Throwable::class);
});
