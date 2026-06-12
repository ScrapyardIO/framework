<?php

namespace RealityInterface\Sensors\Applied\Distance;

use RealityInterface\Sensors\Attributes\MeasuresDistance;
use RealityInterface\Sensors\Contracts\Applied\Distance\PulseDerivedDistanceSensor;
use RealityInterface\Sensors\Enums\LengthUnit;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\Exceptions\SensorException;
use RealityInterface\Sensors\SensorChip;

class UltrasonicDistanceSensor extends DistanceSensor
{
    public function getDistance(): int|float
    {
        /** @var PulseDerivedDistanceSensor $sensor */
        $sensor = &$this->sensor;

        return $sensor->getDistance($this->units);
    }

    public function readDistance(): int|float
    {
        /** @var PulseDerivedDistanceSensor $sensor */
        $sensor = &$this->sensor;
        $mm = $sensor->getDistance(LengthUnit::CM);

        return match ($this->units) {
            LengthUnit::CM => $mm / 10.0,
            LengthUnit::M => $mm / 1000.0,
            LengthUnit::IN => $mm / 25.4,
            LengthUnit::FT => $mm / 304.8,
            LengthUnit::YD => $mm / 914.4,
            default => (float) $mm,
        };
    }

    public function measure(): DistanceSensorReading
    {
        $this->pulse();
        $value = $this->readDistance();

        return new DistanceSensorReading(
            SensorType::ULTRASONIC,
            $value,
            $this->units,
            strtotime('now')
        );
    }

    public static function as(SensorChip $circuit): static
    {
        $attr = reflect_class($circuit, MeasuresDistance::class);
        if ($attr->getName() == MeasuresDistance::class) {
            $sensor_type = array_filter($attr->getArguments(), fn ($item) => $item instanceof SensorType);

            if (! is_null($sensor_type)) {
                if ($sensor_type[0] == SensorType::ULTRASONIC) {
                    return new static($circuit);
                }

                throw SensorException::incorrectSensorType($circuit::class, SensorType::ULTRASONIC);
            }

            throw SensorException::sensorTypeNotFound($circuit::class, 'SensorType');
        }

        throw SensorException::missingRequiredAbility('UltrasonicDistanceSensor', $circuit::class, 'MeasuresDistance');
    }
}
