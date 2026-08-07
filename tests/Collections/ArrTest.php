<?php

use Fabricate\NutsAndBolts\Arr;

test('arr get retrieves nested values with dot notation', function () {
    $array = ['app' => ['name' => 'ScrapyardIO']];

    expect(Arr::get($array, 'app.name'))->toBe('ScrapyardIO')
        ->and(Arr::get($array, 'app.missing', 'fallback'))->toBe('fallback');
});

test('arr get returns default for missing keys', function () {
    expect(Arr::get([], 'missing'))->toBeNull()
        ->and(Arr::get([], 'missing', 'default'))->toBe('default');
});
