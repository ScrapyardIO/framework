<?php

namespace Fabricate\NutsAndBolts\MagicAliases;

use Fabricate\Actuation\ActuatorFactory;

/**
 * @method static ActuatorFactory type(string $actuator_type)
 * @method static ActuatorFactory circuit(string $circuit)
 */
class Actuator extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'actuator';
    }
}
