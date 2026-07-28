<?php

namespace Fabricate\NutsAndBolts\MagicAliases;

/**
 * @method static \Fabricate\Contracts\Sensors\Sensor type(string $driver)
 * @method static void addSensor(string $name, string $class_name)
 *
 * @see \Fabricate\Sensors\SensorRegistry
 */
class Sensor extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'sensor';
    }
}