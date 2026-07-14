<?php

namespace ScrapyardIO\NutsAndBolts\MagicAliases;

/**
 * @see \BareMetal\Process\Factory
 */
class Process extends MagicAlias
{
    /**
     * Get the registered name of the component.
     */
    protected static function getAliasAccessor(): string
    {
        return 'process';
    }
}
