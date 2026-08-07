<?php

namespace Fabricate\Core\MagicAliases;

use Fabricate\MagicAliases\MagicAlias;

/**
 * @method static bool supported(string $key, string $cipher)
 * @method static string generateKey(string $cipher)
 * @method static string encrypt(mixed $value, bool $serialize = true)
 * @method static string encryptString(string $value)
 * @method static mixed decrypt(string $payload, bool $unserialize = true)
 * @method static string decryptString(string $payload)
 * @method static bool appearsEncrypted(mixed $value)
 * @method static string getKey()
 * @method static array getAllKeys()
 * @method static array getPreviousKeys()
 * @method static \Fabricate\Encryption\Encrypter previousKeys(array $keys)
 *
 * @see \Fabricate\Encryption\Encrypter
 */
class Crypt extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'encrypter';
    }
}
