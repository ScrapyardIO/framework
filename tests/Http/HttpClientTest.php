<?php

use Fabricate\Core\MagicAliases\Http;
use Fabricate\Core\Machine;
use Fabricate\Core\Providers\HttpServiceProvider;
use Fabricate\Http\Client\Factory;
use Fabricate\MagicAliases\MagicAlias;

beforeEach(function () {
    $this->container = new Machine(sys_get_temp_dir());
    $this->container->register(HttpServiceProvider::class);

    MagicAlias::clearResolvedInstances();
    MagicAlias::setMagicAliasApplication($this->container);
});

afterEach(function () {
    MagicAlias::clearResolvedInstances();
    MagicAlias::setMagicAliasApplication(null);
});

test('http magic alias fakes an outbound get request', function () {
    Http::fake([
        'https://device.test/status' => Http::response([
            'status' => 'ready',
        ], 200),
    ]);

    $response = Http::get('https://device.test/status');

    expect($response->successful())->toBeTrue()
        ->and($response->json('status'))->toBe('ready');

    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && $request->url() === 'https://device.test/status');
});

test('http service provider binds the factory as a singleton', function () {
    expect($this->container->make('http'))->toBeInstanceOf(Factory::class)
        ->and($this->container->make('http'))->toBe($this->container->make(Factory::class));
});
