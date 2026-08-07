<?php

require __DIR__.'/../Console/helpers.php';

use Fabricate\Contracts\Encryption\DecryptException;
use Fabricate\Core\MagicAliases\Crypt;
use Fabricate\Encryption\Cipher;
use Fabricate\Encryption\Encrypter;
use Fabricate\Encryption\MissingAppKeyException;

test('encrypter generateKey returns bytes for the requested cipher', function () {
    $key = Encrypter::generateKey('AES-256-CBC');

    expect($key)->toBeString()
        ->and(strlen($key))->toBe(Cipher::AES_256_CBC->keySize());
});

test('encrypter generateKey falls back to aes-256-cbc for unknown ciphers', function () {
    $key = Encrypter::generateKey('not-a-cipher');

    expect(strlen($key))->toBe(Cipher::AES_256_CBC->keySize());
});

test('encrypter supported validates key length for cipher', function () {
    expect(Encrypter::supported(random_bytes(32), 'AES-256-CBC'))->toBeTrue()
        ->and(Encrypter::supported(random_bytes(16), 'AES-256-CBC'))->toBeFalse()
        ->and(Encrypter::supported(random_bytes(16), 'AES-128-GCM'))->toBeTrue()
        ->and(Encrypter::supported(random_bytes(32), 'unknown'))->toBeFalse();
});

test('encrypter encrypt and decrypt round trip', function () {
    $encrypter = new Encrypter(Encrypter::generateKey('AES-256-CBC'), 'AES-256-CBC');

    $payload = $encrypter->encrypt(['board' => 'pi']);

    expect($encrypter->decrypt($payload))->toBe(['board' => 'pi'])
        ->and($encrypter->decryptString($encrypter->encryptString('edge')))->toBe('edge')
        ->and(Encrypter::appearsEncrypted($payload))->toBeTrue()
        ->and(Encrypter::appearsEncrypted('not-encrypted'))->toBeFalse();
});

test('encrypter aes-256-gcm round trip', function () {
    $encrypter = new Encrypter(Encrypter::generateKey('AES-256-GCM'), 'AES-256-GCM');

    expect($encrypter->decryptString($encrypter->encryptString('aead')))->toBe('aead');
});

test('encrypter decrypts with previous keys after rotation', function () {
    $legacy = Encrypter::generateKey('AES-256-CBC');
    $current = Encrypter::generateKey('AES-256-CBC');

    $old = new Encrypter($legacy, 'AES-256-CBC');
    $payload = $old->encryptString('rotated');

    $new = (new Encrypter($current, 'AES-256-CBC'))->previousKeys([$legacy]);

    expect($new->decryptString($payload))->toBe('rotated');
});

test('encrypter rejects invalid payloads', function () {
    $encrypter = new Encrypter(Encrypter::generateKey('AES-256-CBC'), 'AES-256-CBC');

    expect(fn () => $encrypter->decrypt('totally-not-valid'))
        ->toThrow(DecryptException::class);
});

test('encrypter rejects wrong key length', function () {
    expect(fn () => new Encrypter('too-short', 'AES-256-CBC'))
        ->toThrow(RuntimeException::class);
});

test('encrypter binding and Crypt magic alias resolve through the container', function () {
    $basePath = createConsoleTestBasePath();
    $raw = Encrypter::generateKey('AES-256-CBC');

    try {
        $app = bootstrapConsoleMachine($basePath);
        // Test bootstrap skips dotenv; set the key the provider reads from config.
        $app['config']->set('machine.key', 'base64:'.base64_encode($raw));
        $app['config']->set('machine.cipher', 'AES-256-CBC');

        expect($app->bound('encrypter'))->toBeTrue()
            ->and($app->make('encrypter'))->toBeInstanceOf(Encrypter::class)
            ->and(Crypt::encrypt('hello'))->toBeString()
            ->and(Crypt::decrypt(Crypt::encrypt('hello')))->toBe('hello')
            ->and(encrypt('cli'))->toBeString()
            ->and(decrypt(encrypt('cli')))->toBe('cli');
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});

test('encrypter binding throws when machine key is missing', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);

        $app->make('encrypter');
    } catch (MissingAppKeyException $e) {
        expect($e)->toBeInstanceOf(MissingAppKeyException::class);

        return;
    } finally {
        destroyConsoleTestBasePath($basePath);
    }

    expect(false)->toBeTrue('Expected MissingAppKeyException');
});
