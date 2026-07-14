<?php

namespace ScrapyardIO\NutsAndBolts\MagicAliases;

use Illuminate\Contracts\Broadcasting\BroadcastingFactory as BroadcastingFactoryInterface;

class Broadcast extends MagicAlias
{
    /**
     * Get the registered name of the component.
     */
    protected static function getAliasAccessor(): string
    {
        return BroadcastingFactoryInterface::class;
    }

}
