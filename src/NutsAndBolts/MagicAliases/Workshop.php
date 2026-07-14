<?php

namespace ScrapyardIO\NutsAndBolts\MagicAliases;

use BareMetal\Contracts\Console\Kernel as ConsoleKernelContract;

/**
 * @see \BareMetal\Core\Console\ConsoleKernel
 */
class Workshop extends MagicAlias
{
    /**
     * Get the registered name of the component.
     */
    protected static function getAliasAccessor(): string
    {
        return ConsoleKernelContract::class;
    }
}
