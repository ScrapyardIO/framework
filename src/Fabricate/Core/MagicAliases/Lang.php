<?php

namespace Fabricate\Core\MagicAliases;

use Fabricate\MagicAliases\MagicAlias;

/**
 * @method static bool hasForLocale(string $key, string|null $locale = null)
 * @method static bool has(string $key, string|null $locale = null, bool|null $fallback = true)
 * @method static mixed get(string $key, array $replace = [], string|null $locale = null, bool|null $fallback = true)
 * @method static string choice(string $key, \Countable|int|float|array $number, array $replace = [], string|null $locale = null)
 * @method static void addLines(array $lines, string $locale, string $namespace = '*')
 * @method static void load(string $namespace, string $group, string $locale, string|null $path = null)
 * @method static \Fabricate\Translation\Translator handleMissingKeysUsing(callable|null $callback)
 * @method static \Fabricate\Translation\Translator determineLocalesUsing(callable $callback)
 * @method static string locale()
 * @method static \Fabricate\Translation\Translator setLocale(string $locale)
 * @method static string getLocale()
 * @method static \Fabricate\Translation\Translator setFallback(string $fallback)
 * @method static string getFallback()
 * @method static void setLoaded(array $loaded)
 * @method static void stringable(callable|string $class, callable|null $handler = null)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see \Fabricate\Translation\Translator
 */
class Lang extends MagicAlias
{
    protected static function getMagicAliasAccessor(): string
    {
        return 'translator';
    }
}
