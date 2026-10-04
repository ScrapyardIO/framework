<?php

use GeneralPurposeIO\Contracts\Digital\DigitalIOException;
use GeneralPurposeIO\Digital\DigitalOConnectionManager;
use GeneralPurposeIO\Digital\NoneDigitalIOConnectionDriver;
use ScrapyardIO\Tests\Fixtures\FakeDigitalIOConnectionDriver;
use Voyager\Config\Repository;
use Voyager\Contracts\IOPools\Loop;
use Voyager\IOPools\EventLoop;
use Voyager\Vessel\ControlPanel;

function digitalApp(string $default = 'fake'): ControlPanel
{
    $app = new ControlPanel;
    $app->registerInstance('config', new Repository(['gpio' => ['protocols' => ['digital-in' => ['default' => $default]]]]));

    return $app;
}

function fakeManager(ControlPanel $app): DigitalOConnectionManager
{
    $manager = new DigitalOConnectionManager($app);
    $manager->extend('fake', fn () => new FakeDigitalIOConnectionDriver);

    return $manager;
}

it('gives a pin no loop while none is bound', function () {
    $driver = fakeManager(digitalApp())->driver();
    $driver->connectTo('bench')->register();

    expect(fn () => $driver->input('bench', 3)->watch())->toThrow(DigitalIOException::class, 'No Event Loop');
});

it('finds a loop bound after the driver was built', function () {
    $app = digitalApp();
    $driver = fakeManager($app)->driver();
    $driver->connectTo('bench')->register();
    $pin = $driver->input('bench', 3);

    $app->registerInstance(Loop::class, $loop = testLoop());
    $pin->emit('r');

    $pin->watch();
    $edge = $pin->listen(0, true, true);
    $pin->unwatch();

    expect($edge?->device)->toBe('bench');
});

it('treats a loop alias with nothing behind it as no loop', function () {
    $app = digitalApp();
    $app->alias('event-loop', Loop::class);

    $driver = fakeManager($app)->driver();
    $driver->connectTo('bench')->register();

    expect($app->isBound(Loop::class))->toBeTrue()
        ->and(fn () => $driver->input('bench', 3)->watch())->toThrow(DigitalIOException::class, 'No Event Loop');
});

it('falls back to the none driver, which refuses to connect and has nothing to disconnect', function () {
    $driver = (new DigitalOConnectionManager(digitalApp('none')))->driver();

    $driver->disconnect('bench');

    expect($driver)->toBeInstanceOf(NoneDigitalIOConnectionDriver::class)
        ->and(fn () => $driver->connectTo('bench'))->toThrow(DigitalIOException::class, 'No DigitalIO connection driver');
});
