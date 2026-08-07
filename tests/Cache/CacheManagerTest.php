<?php

use Fabricate\Cache\ArrayStore;
use Fabricate\Cache\CacheManager;
use Fabricate\Cache\CacheRepository;
use Fabricate\Chassis\Chassis;
use Fabricate\Config\Repository as ConfigRepository;

test('cache manager exposes a public store api', function () {
    $reflection = new ReflectionClass(CacheManager::class);

    expect($reflection->isInstantiable())->toBeTrue()
        ->and($reflection->hasMethod('store'))->toBeTrue()
        ->and($reflection->getMethod('store')->isPublic())->toBeTrue();
});

test('cache manager resolves array store from config', function () {
    $container = new Chassis;

    $container->instance('config', new ConfigRepository([
        'cache' => [
            'default' => 'array',
            'prefix' => 'test-cache-',
            'stores' => [
                'array' => [
                    'driver' => 'array',
                    'serialize' => false,
                ],
            ],
        ],
    ]));

    $manager = new CacheManager($container);
    $repository = $manager->store('array');

    expect($repository)->toBeInstanceOf(CacheRepository::class);

    $repository->put('greeting', 'hello', 60);

    expect($repository->get('greeting'))->toBe('hello');
});

test('array store put get forget and flush', function () {
    $store = new ArrayStore;

    expect($store->put('key', 'value', 60))->toBeTrue()
        ->and($store->get('key'))->toBe('value');

    expect($store->forget('key'))->toBeTrue()
        ->and($store->get('key'))->toBeNull();

    $store->put('a', 1, 60);
    $store->put('b', 2, 60);

    expect($store->flush())->toBeTrue()
        ->and($store->get('a'))->toBeNull()
        ->and($store->get('b'))->toBeNull();
});
