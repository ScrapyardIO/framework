<?php

use Fabricate\Config\Repository;

test('repository get and set dot notation values', function () {
    $config = new Repository;

    $config->set('app.name', 'ScrapyardIO');

    expect($config->get('app.name'))->toBe('ScrapyardIO')
        ->and($config->has('app.name'))->toBeTrue()
        ->and($config->has('app.missing'))->toBeFalse();
});

test('repository string getter returns typed string values', function () {
    $config = new Repository(['app' => ['name' => 'ScrapyardIO']]);

    expect($config->string('app.name'))->toBe('ScrapyardIO');
});

test('repository string getter throws for non string values', function () {
    $config = new Repository(['app' => ['debug' => true]]);

    $config->string('app.debug');
})->throws(\InvalidArgumentException::class);
