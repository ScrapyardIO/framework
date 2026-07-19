<?php

namespace Fabricate\NutsAndBolts\MagicAliases;

use Fabricate\Sensors\SensorFactory;

/**
 * @method static SensorFactory type(string $sensor_type)
 * @method static SensorFactory circuit(string $circuit)

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
