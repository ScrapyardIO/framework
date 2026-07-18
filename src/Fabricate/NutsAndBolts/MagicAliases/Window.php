<?php

namespace Fabricate\NutsAndBolts\MagicAliases;

class Window extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'window';
    }
}

