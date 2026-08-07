<?php

namespace Fabricate\Core\Providers;

use Fabricate\Encryption\Encrypter;
use Fabricate\Encryption\MissingAppKeyException;
use Fabricate\NutsAndBolts\ServiceProvider;
use Fabricate\NutsAndBolts\Str;

/**
 * Binds the Encrypter as `encrypter`.
 *
 * Core owns this glue — not fabricate/encryption.
 */
class EncryptionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->container->singleton('encrypter', function ($app) {
            $config = $app->make('config')->get('machine');

            return (new Encrypter($this->parseKey($config), $config['cipher'] ?? 'AES-256-CBC'))
                ->previousKeys(array_map(
                    fn ($key) => $this->parseKey(['key' => $key]),
                    $config['previous_keys'] ?? []
                ));
        });
    }

    /**
     * Parse the encryption key.
     *
     * @param  array{key?: string|null}  $config
     */
    protected function parseKey(array $config): string
    {
        if (Str::startsWith($key = $this->key($config), $prefix = 'base64:')) {
            $key = base64_decode(Str::after($key, $prefix));
        }

        return $key;
    }

    /**
     * Extract the encryption key from the given configuration.
     *
     * @param  array{key?: string|null}  $config
     *
     * @throws \Fabricate\Encryption\MissingAppKeyException
     */
    protected function key(array $config): string
    {
        $key = $config['key'] ?? null;

        if (empty($key)) {
            throw new MissingAppKeyException;
        }

        return $key;
    }
}
