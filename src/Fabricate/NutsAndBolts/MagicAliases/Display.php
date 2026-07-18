<?php

namespace Fabricate\NutsAndBolts\MagicAliases;

class Display extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'display';
    }
}

