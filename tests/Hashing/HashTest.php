<?php

require __DIR__.'/../Console/helpers.php';

use Fabricate\Contracts\Hashing\Hasher;
use Fabricate\Core\MagicAliases\Hash;
use Fabricate\Hashing\BcryptHasher;
use Fabricate\Hashing\HashManager;

test('bcrypt hasher make check and needsRehash', function () {
    $hasher = new BcryptHasher(['rounds' => 4]);

    $digest = $hasher->make('edge-secret');

    expect($hasher->check('edge-secret', $digest))->toBeTrue()
        ->and($hasher->check('wrong', $digest))->toBeFalse()
        ->and($hasher->check('edge-secret', null))->toBeFalse()
        ->and($hasher->check('edge-secret', ''))->toBeFalse()
        ->and($hasher->needsRehash($digest, ['rounds' => 8]))->toBeTrue()
        ->and($hasher->needsRehash($digest, ['rounds' => 4]))->toBeFalse()
        ->and($hasher->info($digest)['algoName'])->toBe('bcrypt');
});

test('hash manager delegates to configured driver', function () {
    $config = new Fabricate\Config\Repository([
        'hashing' => [
            'driver' => 'bcrypt',
            'bcrypt' => ['rounds' => 4, 'verify' => false],
            'argon' => ['memory' => 1024, 'threads' => 1, 'time' => 1, 'verify' => false],
        ],
    ]);

    $app = Mockery::mock(Fabricate\Contracts\Core\Program::class);
    $app->shouldReceive('make')->with('config')->andReturn($config);

    $manager = new HashManager($app);
    $digest = $manager->make('machine-token');

    expect($manager->check('machine-token', $digest))->toBeTrue()
        ->and($manager->isHashed($digest))->toBeTrue()
        ->and($manager->isHashed('plain-text'))->toBeFalse()
        ->and($manager->driver('bcrypt'))->toBeInstanceOf(BcryptHasher::class);
});

test('hash binding and Hash magic alias resolve through the container', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);

        expect($app->bound('hash'))->toBeTrue()
            ->and($app->make('hash'))->toBeInstanceOf(HashManager::class)
            ->and($app->make(Hasher::class))->toBeInstanceOf(HashManager::class);

        $digest = Hash::make('hello');

        expect(Hash::check('hello', $digest))->toBeTrue()
            ->and(Hash::check('nope', $digest))->toBeFalse()
            ->and(bcrypt('cli'))->toBeString()
            ->and(Hash::check('cli', bcrypt('cli')))->toBeTrue();
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});
