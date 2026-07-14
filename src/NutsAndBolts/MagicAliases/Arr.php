<?php

namespace ScrapyardIO\NutsAndBolts\MagicAliases;

use ScrapyardIO\NutsAndBolts\Arr as SupportArr;

/**
 * Static utility alias — forwards to ScrapyardIO\NutsAndBolts\Arr.
 *
 * @see \ScrapyardIO\NutsAndBolts\Arr
 */
class Arr extends MagicAlias
{
    /**
     * Get the registered name of the component.
     */
    protected static function getAliasAccessor(): string
    {
        return SupportArr::class;
    }

    /**
     * Forward static calls to the Arr utility class.
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        return SupportArr::$method(...$args);
    }
}
