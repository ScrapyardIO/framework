<?php

namespace Fabricate\Database\Polisher\Casts;

use Fabricate\Contracts\Database\Polisher\Castable;
use Fabricate\Contracts\Database\Polisher\CastsAttributes;
use Fabricate\NutsAndBolts\HtmlString;

class AsHtmlString implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Fabricate\Contracts\Database\Polisher\CastsAttributes<\Fabricate\NutsAndBolts\HtmlString, string|HtmlString>
     */
    public static function castUsing(array $arguments)
    {
        return new class implements CastsAttributes
        {
            public function get($model, $key, $value, $attributes)
            {
                return isset($value) ? new HtmlString($value) : null;
            }

            public function set($model, $key, $value, $attributes)
            {
                return isset($value) ? (string) $value : null;
            }
        };
    }
}
