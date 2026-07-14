<?php

namespace ScrapyardIO\NutsAndBolts\MagicAliases;

use ScrapyardIO\NutsAndBolts\Str as SupportStr;

/**
 * Static utility alias — forwards to ScrapyardIO\NutsAndBolts\Str.
 *
 * @see \ScrapyardIO\NutsAndBolts\Str
 */
class Str extends MagicAlias
{
    /**
     * Get the registered name of the component.
     */
    protected static function getAliasAccessor(): string
    {
        return SupportStr::class;
    }

    /**
     * Forward static calls to the Str utility class.
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        return SupportStr::$method(...$args);
    }
}
