<?php

namespace Fabricate\NutsAndBolts;

/**
 * Minimal English pluralizer for Str helpers (validation / fakes).
 *
 * A full Doctrine inflector port is deferred — this covers framework test paths.
 */
class Pluralizer
{
    /**
     * @var list<string>
     */
    public static array $uncountable = [
        'recommended',
        'related',
    ];

    public static function plural(string $value, int|array|\Countable $count = 2): string
    {
        if (is_countable($count)) {
            $count = count($count);
        }

        if ((int) abs($count) === 1 || in_array(strtolower($value), static::$uncountable, true)) {
            return $value;
        }

        if (str_ends_with($value, 'y') && ! str_ends_with($value, 'ay') && ! str_ends_with($value, 'ey')) {
            return substr($value, 0, -1).'ies';
        }

        if (str_ends_with($value, 's')) {
            return $value;
        }

        return $value.'s';
    }

    public static function singular(string $value): string
    {
        if (str_ends_with($value, 'ies')) {
            return substr($value, 0, -3).'y';
        }

        if (str_ends_with($value, 's') && ! str_ends_with($value, 'ss')) {
            return substr($value, 0, -1);
        }

        return $value;
    }
}
