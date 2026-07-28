<?php

namespace Fabricate\NutsAndBolts\Helpers;

use UnitEnum;
use BackedEnum;

if (! function_exists('Fabricate\NutsAndBolts\Helpers\enum_value')) {
    /**
     * Return a scalar value for the given value that might be an enum.
     *
     * @param  TValue  $value
     * @param  callable(TValue): TDefault|null  $default
     * @return ($value is empty ? TDefault : mixed)
     * @internal
     *
     * @template TValue
     * @template TDefault
     *
     */
    function enum_value($value, ?callable $default = null): mixed
    {
        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof UnitEnum => $value->name,

            default => $value ?? value($default),
        };
    }
}