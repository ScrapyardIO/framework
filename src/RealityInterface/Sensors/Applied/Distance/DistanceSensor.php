<?php

namespace RealityInterface\Sensors\Applied\Distance;

use RealityInterface\Sensors\Attributes\MeasuresDistance;
use RealityInterface\Sensors\Contracts\Applied\Distance\GenericDistanceSensor;
use RealityInterface\Sensors\Enums\LengthUnit;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\Exceptions\SensorException;
use RealityInterface\Sensors\Sensor;
use RealityInterface\Sensors\SensorChip;

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
        /** @var GenericDistanceSensor $sensor */
        $sensor = &$this->sensor;

        return $sensor->getDistance();
    }

    public function measure(): DistanceSensorReading
    {
        /** @var GenericDistanceSensor $sensor */
        $sensor = &$this->sensor;
        $value = $sensor->getDistance();

        return new DistanceSensorReading(
            SensorType::DISTANCE,
            $value,
            $this->units,
            strtotime('now')
        );
    }

    public static function as(SensorChip $circuit): static
    {
        $attr = reflect_class($circuit, MeasuresDistance::class);
        if ($attr->getName() == MeasuresDistance::class) {
            return new static($circuit);
        }

        throw SensorException::missingRequiredAbility('DistanceSensor', $circuit::class, 'MeasuresDistance');
    }
}
