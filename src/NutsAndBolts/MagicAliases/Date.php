<?php

namespace ScrapyardIO\NutsAndBolts\MagicAliases;

use ScrapyardIO\NutsAndBolts\Carbon;

/**
 * Date alias — proxies to Carbon until a DateFactory is ported.
 *
 * @see \ScrapyardIO\NutsAndBolts\Carbon
 */
class Date extends MagicAlias
{
    /**
     * Get the registered name of the component.
     */
    protected static function getAliasAccessor(): string
    {
        return 'date';
    }

    /**
     * Forward static calls to Carbon when no date factory is bound.
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        if (static::$app?->bound('date')) {
            return parent::__callStatic($method, $args);
        }

        return Carbon::$method(...$args);
    }
}
