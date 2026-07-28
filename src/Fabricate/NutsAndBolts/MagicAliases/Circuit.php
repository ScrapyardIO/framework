<?php

namespace Fabricate\NutsAndBolts\MagicAliases;

/**
 * @method static \Fabricate\Contracts\Circuits\IntegratedCircuit driver(string $driver)
 * @method static void addCircuit(string $name, string $class_name)
 *
 * @see \Fabricate\Circuits\CircuitRegistry
 */
class Circuit extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'circuit';
    }
}