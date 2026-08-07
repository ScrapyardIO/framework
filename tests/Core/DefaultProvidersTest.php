<?php

use Fabricate\Core\AliasLoader;
use Fabricate\Core\DefaultProviders;
use Fabricate\Core\MagicAliases\Bus;
use Fabricate\Core\MagicAliases\Concurrency;
use Fabricate\Core\MagicAliases\Crypt;
use Fabricate\Core\MagicAliases\Hash;
use Fabricate\Core\MagicAliases\Http;
use Fabricate\Core\MagicAliases\Process;
use Fabricate\Core\MagicAliases\Queue;
use Fabricate\Core\Providers\BusServiceProvider;
use Fabricate\Core\Providers\CacheServiceProvider;
use Fabricate\Core\Providers\ConcurrencyServiceProvider;
use Fabricate\Core\Providers\CoreServiceProvider;
use Fabricate\Core\Providers\EncryptionServiceProvider;
use Fabricate\Core\Providers\FilesystemServiceProvider;
use Fabricate\Core\Providers\HashServiceProvider;
use Fabricate\Core\Providers\HttpServiceProvider;
use Fabricate\Core\Providers\LogServiceProvider;
use Fabricate\Core\Providers\ProcessServiceProvider;
use Fabricate\Core\Providers\QueueServiceProvider;
use Fabricate\Core\Providers\RedisServiceProvider;
use Fabricate\Core\Providers\TranslationServiceProvider;
use Fabricate\Core\Providers\ValidationServiceProvider;

test('default providers includes core framework providers', function () {
    $providers = DefaultProviders::make()->toArray();

    expect($providers)->toContain(
        BusServiceProvider::class,
        CacheServiceProvider::class,
        ConcurrencyServiceProvider::class,
        CoreServiceProvider::class,
        EncryptionServiceProvider::class,
        FilesystemServiceProvider::class,
        HashServiceProvider::class,
        HttpServiceProvider::class,
        LogServiceProvider::class,
        ProcessServiceProvider::class,
        QueueServiceProvider::class,
        RedisServiceProvider::class,
        TranslationServiceProvider::class,
        ValidationServiceProvider::class,
    );
});

test('default aliases include Process and Concurrency', function () {
    expect(AliasLoader::defaultAliases()->all())->toMatchArray([
        'Bus' => Bus::class,
        'Cache' => \Fabricate\Core\MagicAliases\Cache::class,
        'Concurrency' => Concurrency::class,
        'Crypt' => Crypt::class,
        'Hash' => Hash::class,
        'Http' => Http::class,
        'Lang' => \Fabricate\Core\MagicAliases\Lang::class,
        'Process' => Process::class,
        'Queue' => Queue::class,
        'Redis' => \Fabricate\Core\MagicAliases\Redis::class,
        'Validator' => \Fabricate\Core\MagicAliases\Validator::class,
    ]);
});

test('default aliases include Crypt Hash Cache and Redis', function () {
    expect(AliasLoader::defaultAliases()->all())->toMatchArray([
        'Cache' => \Fabricate\Core\MagicAliases\Cache::class,
        'Crypt' => Crypt::class,
        'Hash' => Hash::class,
        'Redis' => \Fabricate\Core\MagicAliases\Redis::class,
    ]);
});

test('default providers except removes requested providers', function () {
    $providers = DefaultProviders::make()
        ->except([LogServiceProvider::class])
        ->toArray();

    expect($providers)->not->toContain(LogServiceProvider::class)
        ->and($providers)->toContain(CoreServiceProvider::class);
});
