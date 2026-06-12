<?php

namespace RealityInterface\Sensors\Applied\Accelerometry;

use RealityInterface\Sensors\Attributes\MeasuresAcceleration;
use RealityInterface\Sensors\Contracts\Applied\Accelerometry\GenericAccelerometer;
use RealityInterface\Sensors\Exceptions\SensorException;
use RealityInterface\Sensors\Sensor;
use RealityInterface\Sensors\SensorChip;

class Accelerometer extends Sensor
{
    public function getAcceleration(): array
    {
        /** @var GenericAccelerometer $sensor */
        $sensor = &$this->sensor;

        return $sensor->getAcceleration();
    }

    public static function as(SensorChip $circuit): static
    {
        $attr = reflect_class($circuit, MeasuresAcceleration::class);
        if ($attr->getName() == MeasuresAcceleration::class) {
            return new static($circuit);
        }

        throw SensorException::missingRequiredAbility('Accelerometer', $circuit::class, 'MeasuresAcceleration');
    }
}
