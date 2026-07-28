<?php

namespace Fabricate\Encryption;

/**
 * Minimal encryption helpers used by workshop commands.
 *
 * Full encrypt/decrypt support can land with EncryptionServiceProvider later;
 * key generation only needs cipher key sizes.
 */
class Encrypter
{
    /**
     * The supported cipher algorithms and their properties.
     *
     * @var array<string, array{size: int, aead: bool}>
     */
    private static array $supportedCiphers = [
        'aes-128-cbc' => ['size' => 16, 'aead' => false],
        'aes-256-cbc' => ['size' => 32, 'aead' => false],
        'aes-128-gcm' => ['size' => 16, 'aead' => true],
        'aes-256-gcm' => ['size' => 32, 'aead' => true],
    ];

    /**
     * Determine if the given key and cipher combination is valid.
     *
     * @param  string  $key
     * @param  string  $cipher
     * @return bool
     */
    public static function supported(string $key, string $cipher): bool
    {
        if (! isset(self::$supportedCiphers[strtolower($cipher)])) {
            return false;
        }

        return mb_strlen($key, '8bit') === self::$supportedCiphers[strtolower($cipher)]['size'];
    }

    /**
     * Create a new encryption key for the given cipher.
     *
     * @param  string  $cipher
     * @return string
     */
    public static function generateKey(string $cipher): string
    {
        return random_bytes(self::$supportedCiphers[strtolower($cipher)]['size'] ?? 32);
    }
}
