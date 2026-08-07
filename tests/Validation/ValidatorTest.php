<?php

use Fabricate\Chassis\Chassis;
use Fabricate\Core\AliasLoader;
use Fabricate\Core\MagicAliases\Validator as ValidatorAlias;
use Fabricate\Core\Providers\TranslationServiceProvider;
use Fabricate\Core\Providers\ValidationServiceProvider;
use Fabricate\Filesystem\Filesystem;
use Fabricate\MagicAliases\MagicAlias;
use Fabricate\Translation\ArrayLoader;
use Fabricate\Translation\Translator;
use Fabricate\Validation\Factory;

function makeValidationContainer(): Chassis
{
    $container = new Chassis;

    $container->instance('config', new \Fabricate\Config\Repository([
        'machine' => [
            'locale' => 'en',
            'fallback_locale' => 'en',
        ],
    ]));

    $container->instance('files', new Filesystem);

    $container->singleton('translation.loader', fn () => new ArrayLoader);
    $container->singleton('translator', function ($app) {
        $loader = $app->make('translation.loader');
        $loader->addMessages('en', 'validation', require dirname(__DIR__, 2).'/src/Fabricate/Translation/lang/en/validation.php');

        return (new Translator($loader, 'en'))->setFallback('en');
    });

    (new ValidationServiceProvider($container))->register();

    return $container;
}

test('validator factory validates required email and min rules', function () {
    $container = makeValidationContainer();
    $factory = $container->make('validator');

    $passes = $factory->make(
        ['email' => 'user@example.com', 'name' => 'Ada'],
        ['email' => 'required|email', 'name' => 'required|min:2']
    );

    expect($passes->passes())->toBeTrue();

    $fails = $factory->make(
        ['email' => 'not-an-email', 'name' => ''],
        ['email' => 'required|email', 'name' => 'required|min:2']
    );

    expect($fails->fails())->toBeTrue()
        ->and($fails->errors()->has('email'))->toBeTrue()
        ->and($fails->errors()->has('name'))->toBeTrue();
});

test('validator binding resolves factory', function () {
    $container = makeValidationContainer();

    expect($container->make('validator'))->toBeInstanceOf(Factory::class);
});

test('validator magic alias resolves from container', function () {
    $container = makeValidationContainer();

    MagicAlias::clearResolvedInstances();
    MagicAlias::setMagicAliasApplication($container);
    AliasLoader::getInstance(['Validator' => ValidatorAlias::class])->register();

    $validator = ValidatorAlias::make(
        ['email' => 'bad'],
        ['email' => 'required|email']
    );

    expect($validator->fails())->toBeTrue();
});

test('translation and validation providers register bindings', function () {
    $container = new Chassis;
    $container->instance('config', new \Fabricate\Config\Repository([
        'machine' => ['locale' => 'en', 'fallback_locale' => 'en'],
    ]));
    $container->instance('files', new Filesystem);
    $container->instance('path.lang', dirname(__DIR__, 2).'/tests/fixtures/lang');

    (new TranslationServiceProvider($container))->register();
    (new ValidationServiceProvider($container))->register();

    expect($container->bound('translator'))->toBeTrue()
        ->and($container->bound('validator'))->toBeTrue()
        ->and($container->bound('translation.loader'))->toBeTrue();
});
