<?php

namespace Fabricate\Core\MagicAliases;

use Fabricate\MagicAliases\MagicAlias;

/**
 * @method static \Fabricate\Validation\Validator make(array $data, array $rules, array $messages = [], array $attributes = [])
 * @method static \Fabricate\Validation\Validator validate(array $data, array $rules, array $messages = [], array $attributes = [])
 * @method static void extend(string $rule, \Closure|string $extension, string|null $message = null)
 * @method static void extendImplicit(string $rule, \Closure|string $extension, string|null $message = null)
 * @method static void extendDependent(string $rule, \Closure|string $extension, string|null $message = null)
 * @method static void replacer(string $rule, \Closure|string $replacer)
 * @method static void includeUnvalidatedArrayKeys()
 * @method static void excludeUnvalidatedArrayKeys()
 * @method static void resolver(\Closure $resolver)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see \Fabricate\Validation\Factory
 */
class Validator extends MagicAlias
{
    protected static function getMagicAliasAccessor(): string
    {
        return 'validator';
    }
}
