<?php

namespace RealityInterface\Displays\Exceptions;

use RuntimeException;

class DisplayException extends RuntimeException
{
    public static function missingRequiredAbility(string $class, string $circuit_class, string $attr): static
    {
        return new static("{$class} requires {$circuit_class} to have the {$attr} attribute.");
    }

    public static function cannotTransmitTo(string $circuit_class, string $interface): static
    {
        return new static("{$circuit_class} cannot receive frames; it does not implement {$interface}.");
    }
}
