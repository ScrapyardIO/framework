<?php

use Fabricate\Config\Repository;

beforeEach(function () {
    Repository::flushMacros();
});

test('macroable registers and invokes static macros', function () {
    Repository::macro('appName', fn () => 'ScrapyardIO');

    expect((new Repository)->appName())->toBe('ScrapyardIO')
        ->and(Repository::hasMacro('appName'))->toBeTrue();
});

test('macroable flush removes registered macros', function () {
    Repository::macro('temporary', fn () => true);

    Repository::flushMacros();

    expect(Repository::hasMacro('temporary'))->toBeFalse();
});
