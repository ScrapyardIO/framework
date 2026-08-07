<?php

use Fabricate\Chassis\Chassis;
use Fabricate\Core\MagicAliases\Event;
use Fabricate\Events\Dispatcher;
use Fabricate\MagicAliases\MagicAlias;
use Fabricate\Testing\Fakes\EventFake;

class TelemetryUploadedFixtureEvent
{
    public function __construct(public string $chip) {}
}

beforeEach(function () {
    $container = new Chassis;
    $container->instance('events', new Dispatcher($container));

    MagicAlias::clearResolvedInstances();
    MagicAlias::setMagicAliasApplication($container);
});

afterEach(function () {
    MagicAlias::clearResolvedInstances();
    MagicAlias::setMagicAliasApplication(null);
});

test('Event::fake swaps the dispatcher for an EventFake and isFake reports true', function () {
    expect(Event::isFake())->toBeFalse();

    $fake = Event::fake();

    expect($fake)->toBeInstanceOf(EventFake::class)
        ->and(Event::isFake())->toBeTrue();
});

test('Event::fake records dispatches for assertDispatched', function () {
    Event::fake();

    Event::dispatch(new TelemetryUploadedFixtureEvent('esp32'));

    Event::assertDispatched(TelemetryUploadedFixtureEvent::class);
    Event::assertDispatchedOnce(TelemetryUploadedFixtureEvent::class);
});

test('Event::fakeExcept fakes everything except the allowed events', function () {
    $heard = [];

    Event::listen(TelemetryUploadedFixtureEvent::class, function () use (&$heard) {
        $heard[] = true;
    });

    Event::fakeExcept(TelemetryUploadedFixtureEvent::class);

    Event::dispatch(new TelemetryUploadedFixtureEvent('rp2040'));

    expect($heard)->toBe([true]);
});
