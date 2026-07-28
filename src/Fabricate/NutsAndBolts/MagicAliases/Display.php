<?php

namespace Fabricate\NutsAndBolts\MagicAliases;

/**
 * @method static \Fabricate\Contracts\Displays\EmbeddedDisplay embedded(string $type, string $circuit)
 * @method static \Fabricate\Contracts\Displays\WindowedDisplay window(string $driver)
 * @method static void addWPanel(string $name, string $class_name)
 * @method static void addEPanel(string $name, string $class_name)
 *
 * @see \Fabricate\Displays\DisplayRegistry
 */
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

