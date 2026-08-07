<?php

use Fabricate\Chassis\Chassis;
use Fabricate\Events\Dispatcher;
use Fabricate\Events\NullDispatcher;

test('null dispatcher swallows dispatch but listen still forwards', function () {
    $container = new Chassis;
    $dispatcher = new Dispatcher($container);
    $null = new NullDispatcher($dispatcher);
    $heard = [];

    $null->listen('board.probed', function () use (&$heard) {
        $heard[] = true;
    });

    expect($dispatcher->hasListeners('board.probed'))->toBeTrue();

    $response = $null->dispatch('board.probed');

    expect($response)->toBeNull()
        ->and($heard)->toBe([]);
});

test('null dispatcher forwards unknown methods to the underlying dispatcher', function () {
    $container = new Chassis;
    $dispatcher = new Dispatcher($container);
    $null = new NullDispatcher($dispatcher);

    $null->listen('board.probed', fn () => null);

    expect($null->getRawListeners())->toBe($dispatcher->getRawListeners())
        ->and($null->hasWildcardListeners('board.probed'))->toBeFalse();
});
