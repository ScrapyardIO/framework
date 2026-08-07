<?php

namespace Fabricate\Encryption;

enum Cipher: string
{
    case AES_128_CBC = 'aes-128-cbc';
    case AES_256_CBC = 'aes-256-cbc';
    case AES_128_GCM = 'aes-128-gcm';
    case AES_256_GCM = 'aes-256-gcm';

    /**
     * Resolve a cipher name to an enum case.
     */
    public static function tryFromName(string $cipher): ?self
    {
        return self::tryFrom(strtolower($cipher));
    }

    /**
     * The required raw key size in bytes.
     */
    public function keySize(): int
    {
        return match ($this) {
            self::AES_128_CBC, self::AES_128_GCM => 16,
            self::AES_256_CBC, self::AES_256_GCM => 32,
        };
    }

    /**
     * Whether the cipher is AEAD-capable.
     */
    public function isAead(): bool
    {
        return match ($this) {
            self::AES_128_GCM, self::AES_256_GCM => true,
            default => false,
        };
    }
}
