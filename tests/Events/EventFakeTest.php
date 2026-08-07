<?php

use Fabricate\Chassis\Chassis;
use Fabricate\Events\Dispatcher;
use Fabricate\Testing\Fakes\Fake;
use Fabricate\Testing\Fakes\EventFake;

class BoardProbedFixtureEvent
{
    public function __construct(public string $chip = 'esp32') {}
}

test('event fake implements the fake marker interface', function () {
    $container = new Chassis;
    $fake = new EventFake(new Dispatcher($container));

    expect($fake)->toBeInstanceOf(Fake::class);
});

test('event fake records dispatched events instead of firing listeners', function () {
    $container = new Chassis;
    $dispatcher = new Dispatcher($container);
    $heard = [];

    $dispatcher->listen(BoardProbedFixtureEvent::class, function () use (&$heard) {
        $heard[] = true;
    });

    $fake = new EventFake($dispatcher);
    $fake->dispatch(new BoardProbedFixtureEvent);

    expect($heard)->toBe([])
        ->and($fake->hasDispatched(BoardProbedFixtureEvent::class))->toBeTrue();

    $fake->assertDispatched(BoardProbedFixtureEvent::class);
    $fake->assertDispatchedOnce(BoardProbedFixtureEvent::class);
});

test('event fake except() lets specific events dispatch for real', function () {
    $container = new Chassis;
    $dispatcher = new Dispatcher($container);
    $heard = [];

    $dispatcher->listen(BoardProbedFixtureEvent::class, function () use (&$heard) {
        $heard[] = true;
    });

    $fake = (new EventFake($dispatcher))->except(BoardProbedFixtureEvent::class);
    $fake->dispatch(new BoardProbedFixtureEvent);

    expect($heard)->toBe([true])
        ->and($fake->hasDispatched(BoardProbedFixtureEvent::class))->toBeFalse();
});

test('event fake assertNotDispatched and assertNothingDispatched pass when untouched', function () {
    $container = new Chassis;
    $fake = new EventFake(new Dispatcher($container));

    $fake->assertNotDispatched(BoardProbedFixtureEvent::class);
    $fake->assertNothingDispatched();
});

test('event fake forwards unhandled dispatcher methods', function () {
    $container = new Chassis;
    $dispatcher = new Dispatcher($container);
    $fake = new EventFake($dispatcher);

    $fake->listen('board.probed', fn () => null);

    expect($fake->hasListeners('board.probed'))->toBeTrue()
        ->and($fake->getRawListeners())->toBe($dispatcher->getRawListeners());
});
