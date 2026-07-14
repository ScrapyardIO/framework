<?php

namespace ScrapyardIO\NutsAndBolts\MagicAliases;

/**
 * @see \BareMetal\Events\Dispatcher
 */
class Event extends MagicAlias
{
    /**
     * Get the registered name of the component.
     */
    protected static function getAliasAccessor(): string
    {
        return 'events';
    }
}
