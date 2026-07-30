<?php

namespace Fabricate\NutsAndBolts\MagicAliases;

/**
 * @method static \Fabricate\Contracts\Actuation\Actuator type(string $type, string $circuit)
 * @method static void addActuator(string $name, string $class_name)
 * @method static array listActuators()
 *
 * @see \Fabricate\Actuation\ActuatorRegistry
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
