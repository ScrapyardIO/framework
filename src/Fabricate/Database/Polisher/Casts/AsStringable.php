<?php

namespace Fabricate\Database\Polisher\Casts;

use Fabricate\Contracts\Database\Polisher\Castable;
use Fabricate\Contracts\Database\Polisher\CastsAttributes;
use Fabricate\NutsAndBolts\Stringable;

class AsStringable implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Fabricate\Contracts\Database\Polisher\CastsAttributes<\Fabricate\NutsAndBolts\Stringable, string|\Stringable>
     */
    public static function castUsing(array $arguments)
    {
        return new class implements CastsAttributes
        {
            public function get($model, $key, $value, $attributes)
            {
                return isset($value) ? new Stringable($value) : null;
            }

            public function set($model, $key, $value, $attributes)
            {
                return isset($value) ? (string) $value : null;
            }
        };
    }
}
