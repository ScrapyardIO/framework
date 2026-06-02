<?php

namespace RealityInterface\Sensors\Applied\Distance;

use BareMetal\IntegratedCircuit;
use RealityInterface\Sensors\Attributes\MeasuresDistance;
use RealityInterface\Sensors\Contracts\Applied\Distance\PulseDerivedDistanceSensor;
use RealityInterface\Sensors\Enums\LengthUnit;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\Exceptions\SensorException;

class UltrasonicDistanceSensor extends DistanceSensor
{
    public function getDistance(): int|float
    {
        $this->pulse();

        return $this->readDistance();
    }

    public function pulse(): void
    {
        /** @var PulseDerivedDistanceSensor $circuit */
        $circuit = &$this->circuit;
        $circuit->firePulse();
    }

    public function readDistance(): int|float
    {
        /** @var PulseDerivedDistanceSensor $circuit */
        $circuit = &$this->circuit;
        $mm = $circuit->readDistance();

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

    public static function as(IntegratedCircuit $circuit): static
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
