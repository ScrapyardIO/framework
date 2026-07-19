<?php

namespace Fabricate\NutsAndBolts\MagicAliases;

class IntegratedCircuit extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'ic';
    }
}
