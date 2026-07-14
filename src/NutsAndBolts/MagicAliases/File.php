<?php

namespace ScrapyardIO\NutsAndBolts\MagicAliases;

/**
 * @see \BareMetal\Filesystem\Filesystem
 */
class File extends MagicAlias
{
    /**
     * Get the registered name of the component.
     */
    protected static function getAliasAccessor(): string
    {
        return 'files';
    }
}
