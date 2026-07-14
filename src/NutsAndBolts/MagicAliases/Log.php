<?php

namespace ScrapyardIO\NutsAndBolts\MagicAliases;

/**
 * @see \Psr\Log\LoggerInterface
 */
class Log extends MagicAlias
{
    /**
     * Get the registered name of the component.
     */
    protected static function getAliasAccessor(): string
    {
        return 'log';
    }
}
