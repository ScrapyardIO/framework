<?php

namespace Fabricate\Core\MagicAliases;

use Fabricate\MagicAliases\MagicAlias;

/**
 * @method static \Fabricate\Contracts\Hashing\Hasher driver(\UnitEnum|string|null $driver = null)
 * @method static array info(string $hashedValue)
 * @method static string make(#[\SensitiveParameter] string $value, array $options = [])
 * @method static bool check(#[\SensitiveParameter] string $value, string $hashedValue, array $options = [])
 * @method static bool needsRehash(string $hashedValue, array $options = [])
 * @method static bool isHashed(#[\SensitiveParameter] string $value)
 * @method static string getDefaultDriver()
 * @method static \Fabricate\Hashing\HashManager extend(string $driver, \Closure $callback)
 * @method static \Fabricate\Hashing\HashManager forgetDrivers()
 *
 * @see \Fabricate\Hashing\HashManager
 */
class Hash extends MagicAlias
{
    /**
     * Get the registered name of the component.
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'hash';
    }
}
