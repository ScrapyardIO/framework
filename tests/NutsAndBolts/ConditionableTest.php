<?php

use Fabricate\NutsAndBolts\Collection;

test('conditionable when applies callback for truthy values', function () {
    $result = collect(['a'])->when(true, fn (Collection $collection) => $collection->push('b'));

    expect($result->all())->toBe(['a', 'b']);
});

test('conditionable unless skips callback for truthy values', function () {
    $result = collect(['a'])->unless(true, fn (Collection $collection) => $collection->push('b'));

    expect($result->all())->toBe(['a']);
});
