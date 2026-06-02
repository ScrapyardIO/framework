<?php

namespace RealityInterface\Sensors\Applied\Distance;

use BareMetal\IntegratedCircuit;
use RealityInterface\Sensors\Attributes\MeasuresDistance;
use RealityInterface\Sensors\Contracts\Applied\Distance\GenericDistanceSensor;
use RealityInterface\Sensors\Enums\LengthUnit;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\Exceptions\SensorException;
use RealityInterface\Sensors\Sensor;

class DistanceSensor extends Sensor
{
    public LengthUnit $units = LengthUnit::CM;

    public function units(LengthUnit $units): static
    {
        $this->units = $units;

        return $this;
    }

    public function getDistance(): int|float
    {
        return $this->circuit->getDistance();
    }

    public function measure(): DistanceSensorReading
    {
        /** @var GenericDistanceSensor $circuit */
        $circuit = &$this->circuit;
        $value = $circuit->getDistance();

        return new DistanceSensorReading(
            SensorType::DISTANCE,
            $value,
            $this->units,
            strtotime('now')
        );
    }

    public static function as(IntegratedCircuit $circuit): static
    {
        $attr = reflect_class($circuit, MeasuresDistance::class);
        if ($attr->getName() == MeasuresDistance::class) {
            return new static($circuit);
        }

        throw SensorException::missingRequiredAbility('DistanceSensor', $circuit::class, 'MeasuresDistance');
    }
}
