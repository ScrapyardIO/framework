<?php

use Fabricate\Contracts\Translation\Translator;
use Fabricate\Filesystem\Filesystem;
use Fabricate\Translation\ArrayLoader;
use Fabricate\Translation\Translator as TranslationTranslator;

test('translator loads validation messages from array loader', function () {
    $loader = new ArrayLoader;
    $loader->addMessages('en', 'validation', [
        'required' => 'The :attribute field is required.',
    ]);

    $translator = new TranslationTranslator($loader, 'en');

    expect($translator->get('validation.required', ['attribute' => 'email']))
        ->toBe('The email field is required.');
});

test('translator implements contract', function () {
    $loader = new ArrayLoader;

    expect(new TranslationTranslator($loader, 'en'))->toBeInstanceOf(Translator::class);
});

test('file loader reads framework validation lang', function () {
    $paths = [dirname(__DIR__, 2).'/src/Fabricate/Translation/lang'];
    $loader = new \Fabricate\Translation\FileLoader(new Filesystem, $paths);

    $lines = $loader->load('en', 'validation', '*');

    expect($lines)->toHaveKey('required')
        ->and($lines['required'])->toContain(':attribute');
});
