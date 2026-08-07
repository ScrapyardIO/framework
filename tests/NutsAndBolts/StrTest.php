<?php

use Fabricate\NutsAndBolts\Str;
use Fabricate\NutsAndBolts\Stringable;

test('str camel converts snake case strings', function () {
    expect(Str::camel('foo_bar'))->toBe('fooBar');
});

test('str snake converts camel case strings', function () {
    expect(Str::snake('fooBar'))->toBe('foo_bar');
});

test('str of returns a fluent stringable', function () {
    $stringable = Str::of('foo_bar');

    expect($stringable)->toBeInstanceOf(Stringable::class)
        ->and($stringable->camel()->value())->toBe('fooBar')
        ->and($stringable->snake()->value())->toBe('foo_bar');
});
