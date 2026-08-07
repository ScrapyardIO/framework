<?php

use Fabricate\Chassis\Chassis;

test('chassis bind resolves concrete implementations', function () {
    $chassis = new Chassis;

    $chassis->bind('greeting', fn () => 'hello');

    expect($chassis->make('greeting'))->toBe('hello');
});

test('chassis singleton returns the same instance', function () {
    $chassis = new Chassis;

    $chassis->singleton('counter', fn () => new stdClass);

    expect($chassis->make('counter'))->toBe($chassis->make('counter'));
});

test('chassis instance registers shared objects', function () {
    $chassis = new Chassis;
    $value = new stdClass;

    $chassis->instance('shared', $value);

    expect($chassis->make('shared'))->toBe($value)
        ->and($chassis->bound('shared'))->toBeTrue();
});
