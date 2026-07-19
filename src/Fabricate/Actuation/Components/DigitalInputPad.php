<?php

namespace Fabricate\Actuation\Components;

use Fabricate\Contracts\Actuation\ActuationException;
use Fabricate\IntegratedCircuits\ActuatorIC;

class DigitalInputPad extends InputPad
{
    public static function buildWith(ActuatorIC $actuator): static
    {
        if (! method_exists($actuator, 'asDigitalButtonPad')) {
            throw new ActuationException(
                $actuator::class.' must expose asDigitalButtonPad() for DigitalInputPad.'
            );
        }

        $pad = $actuator->asDigitalButtonPad();

        if (! $pad instanceof static) {
            throw new ActuationException(
                $actuator::class.'::asDigitalButtonPad() must return '.static::class
            );
        }

        return $pad;
    }
}
