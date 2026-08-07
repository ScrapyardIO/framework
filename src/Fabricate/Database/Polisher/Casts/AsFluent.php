<?php

namespace Fabricate\Database\Polisher\Casts;

use Fabricate\Contracts\Database\Polisher\Castable;
use Fabricate\Contracts\Database\Polisher\CastsAttributes;
use Fabricate\NutsAndBolts\Fluent;

class AsFluent implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Fabricate\Contracts\Database\Polisher\CastsAttributes<\Fabricate\NutsAndBolts\Fluent, string>
     */
    public static function castUsing(array $arguments)
    {
        return new class implements CastsAttributes
        {
            public function get($model, $key, $value, $attributes)
            {
                return isset($value) ? new Fluent(Json::decode($value)) : null;
            }

            public function set($model, $key, $value, $attributes)
            {
                return isset($value) ? [$key => Json::encode($value)] : null;
            }
        };
    }
}
