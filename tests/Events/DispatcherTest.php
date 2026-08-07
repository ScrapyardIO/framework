<?php

use Fabricate\Chassis\Chassis;
use Fabricate\Events\Dispatcher;

test('dispatcher listens and dispatches synchronously', function () {
    $container = new Chassis;
    $dispatcher = new Dispatcher($container);
    $heard = [];

    $dispatcher->listen('user.created', function (int $id) use (&$heard) {
        $heard[] = $id;
    });

    $responses = $dispatcher->dispatch('user.created', ['id' => 1]);

    expect($heard)->toBe([1])
        ->and($dispatcher->hasListeners('user.created'))->toBeTrue()
        ->and($responses)->toBe([null]);
});

test('dispatcher until stops at first non null response', function () {
    $container = new Chassis;
    $dispatcher = new Dispatcher($container);

    $dispatcher->listen('ping', fn () => null);
    $dispatcher->listen('ping', fn () => 'pong');

    expect($dispatcher->until('ping'))->toBe('pong');
});

test('defer buffers dispatched events then flushes them after the callback', function () {
    $container = new Chassis;
    $dispatcher = new Dispatcher($container);
    $heard = [];

    $dispatcher->listen('order.placed', function (int $id) use (&$heard) {
        $heard[] = $id;
    });

    $result = $dispatcher->defer(function () use ($dispatcher, &$heard) {
        $dispatcher->dispatch('order.placed', ['id' => 1]);
        $dispatcher->dispatch('order.placed', ['id' => 2]);

        // Nothing should have fired yet — still buffered.
        expect($heard)->toBe([]);

        return 'callback-result';
    });

    expect($result)->toBe('callback-result')
        ->and($heard)->toBe([1, 2]);
});

test('defer only buffers the events named in the events list', function () {
    $container = new Chassis;
    $dispatcher = new Dispatcher($container);
    $heard = [];

    $dispatcher->listen('kept', function () use (&$heard) {
        $heard[] = 'kept';
    });
    $dispatcher->listen('immediate', function () use (&$heard) {
        $heard[] = 'immediate';
    });

    $dispatcher->defer(function () use ($dispatcher) {
        $dispatcher->dispatch('immediate');
        $dispatcher->dispatch('kept');
    }, ['kept']);

    expect($heard)->toBe(['immediate', 'kept']);
});

test('getRawListeners returns the unprepared listener map', function () {
    $container = new Chassis;
    $dispatcher = new Dispatcher($container);

    $listener = fn () => null;
    $dispatcher->listen('board.probed', $listener);

    expect($dispatcher->getRawListeners())->toBe(['board.probed' => [$listener]]);
});

test('hasWildcardListeners is public and matches wildcard patterns', function () {
    $container = new Chassis;
    $dispatcher = new Dispatcher($container);

    $dispatcher->listen('board.*', fn () => null);

    expect($dispatcher->hasWildcardListeners('board.probed'))->toBeTrue()
        ->and($dispatcher->hasWildcardListeners('chip.probed'))->toBeFalse();
});
