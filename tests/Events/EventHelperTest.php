<?php

use Fabricate\Chassis\Chassis;
use Fabricate\Core\Events\Dispatchable;
use Fabricate\Events\Dispatcher;

class ChipCalibratedFixtureEvent
{
    use Dispatchable;

    public function __construct(public string $chip) {}
}

beforeEach(function () {
    Chassis::setInstance(null);

    $container = new Chassis;
    $container->instance('events', new Dispatcher($container));

    Chassis::setInstance($container);
});

afterEach(function () {
    Chassis::setInstance(null);
});

test('event helper dispatches through the events container binding', function () {
    $heard = [];

    app('events')->listen(ChipCalibratedFixtureEvent::class, function (ChipCalibratedFixtureEvent $event) use (&$heard) {
        $heard[] = $event->chip;
    });

    event(new ChipCalibratedFixtureEvent('esp32'));

    expect($heard)->toBe(['esp32']);
});

test('dispatchable trait dispatches the event with the given arguments', function () {
    $heard = [];

    app('events')->listen(ChipCalibratedFixtureEvent::class, function (ChipCalibratedFixtureEvent $event) use (&$heard) {
        $heard[] = $event->chip;
    });

    ChipCalibratedFixtureEvent::dispatch('rp2040');

    expect($heard)->toBe(['rp2040']);
});

test('dispatchable dispatchIf and dispatchUnless respect the truth test', function () {
    $heard = [];

    app('events')->listen(ChipCalibratedFixtureEvent::class, function (ChipCalibratedFixtureEvent $event) use (&$heard) {
        $heard[] = $event->chip;
    });

    ChipCalibratedFixtureEvent::dispatchIf(false, 'skipped');
    ChipCalibratedFixtureEvent::dispatchIf(true, 'esp32-s3');
    ChipCalibratedFixtureEvent::dispatchUnless(true, 'skipped-too');
    ChipCalibratedFixtureEvent::dispatchUnless(false, 'stm32');

    expect($heard)->toBe(['esp32-s3', 'stm32']);
});
