<?php

namespace RealityInterface\Sensors\Applied\Presence;

use RealityInterface\Sensors\Attributes\MeasuresObjectPresence;
use RealityInterface\Sensors\Exceptions\SensorException;
use RealityInterface\Sensors\Sensor;
use RealityInterface\Sensors\SensorChip;

class HumanPresenceDetector extends Sensor
{
    public static function as(SensorChip $circuit): static
    {
        $attr = reflect_class($circuit, MeasuresObjectPresence::class);
        if ($attr->getName() == MeasuresObjectPresence::class) {
            return new static($circuit);
        }

        throw SensorException::missingRequiredAbility('HumanPresenceDetector', $circuit::class, 'MeasuresObjectPresence');
    }
}
