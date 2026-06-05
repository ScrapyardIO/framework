<?php

namespace RealityInterface\Sensors\Applied\Accelerometry;

use BareMetal\IntegratedCircuit;
use RealityInterface\Sensors\Attributes\MeasuresAcceleration;
use RealityInterface\Sensors\Contracts\Applied\Accelerometry\GenericAccelerometer;
use RealityInterface\Sensors\Exceptions\SensorException;
use RealityInterface\Sensors\Sensor;

class Accelerometer extends Sensor
{
    public function getAcceleration(): array
    {
        /** @var GenericAccelerometer $circuit */
        $circuit = &$this->circuit;

        return $circuit->getAcceleration();
    }

    public static function as(IntegratedCircuit $circuit): static
    {
        $attr = reflect_class($circuit, MeasuresAcceleration::class);
        if ($attr->getName() == MeasuresAcceleration::class) {
            return new static($circuit);
        }

        throw SensorException::missingRequiredAbility('Accelerometer', $circuit::class, 'MeasuresAcceleration');
    }
}
