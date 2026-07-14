<?php

namespace ScrapyardIO\NutsAndBolts\MagicAliases;

/**
 * @see \BareMetal\Config\Repository
 */
class Config extends MagicAlias
{
    /**
     * Get the registered name of the component.
     */
    protected static function getAliasAccessor(): string
    {
        return 'config';
    }
}
