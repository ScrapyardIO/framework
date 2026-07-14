<?php

namespace ScrapyardIO\NutsAndBolts\MagicAliases;

use BareMetal\Contracts\Broadcasting\BroadcastingFactory;

/**
 * @see \BareMetal\Contracts\Broadcasting\BroadcastingFactory
 */
class Broadcast extends MagicAlias
{
    /**
     * Get the registered name of the component.
     */
    protected static function getAliasAccessor(): string
    {
        return BroadcastingFactory::class;
    }
}
