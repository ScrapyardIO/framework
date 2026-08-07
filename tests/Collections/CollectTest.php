<?php

use Fabricate\NutsAndBolts\Collection;

test('collect creates a collection from an array', function () {
    $collection = collect([1, 2, 3]);

    expect($collection)->toBeInstanceOf(Collection::class)
        ->and($collection->all())->toBe([1, 2, 3]);
});

test('collection map transforms items', function () {
    $result = collect([1, 2, 3])->map(fn (int $value) => $value * 2);

    expect($result->all())->toBe([2, 4, 6]);
});

test('collection filter keeps matching items', function () {
    $result = collect([1, 2, 3, 4])->filter(fn (int $value) => $value % 2 === 0);

    expect($result->values()->all())->toBe([2, 4]);
});
