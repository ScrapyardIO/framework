<?php

namespace ScrapyardIO\NutsAndBolts\MagicAliases;

/**
 * @see \BareMetal\Contracts\Hashing\Hasher
 */
class Hash extends MagicAlias
{
    /**
     * Get the registered name of the component.
     */
    protected static function getAliasAccessor(): string
    {
        return 'hash';
    }
}
