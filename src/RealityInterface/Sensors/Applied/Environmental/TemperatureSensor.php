<?php

namespace RealityInterface\Sensors\Applied\Environmental;

use BareMetal\IntegratedCircuit;
use RealityInterface\Sensors\Attributes\MeasuresTemperature;
use RealityInterface\Sensors\Contracts\Applied\Environmental\TemperatureSensor as TemperatureSensorInterface;
use RealityInterface\Sensors\Enums\TemperatureUnit;
use RealityInterface\Sensors\Exceptions\SensorException;

class TemperatureSensor extends EnvironmentalSensor
{
    public function temperature(TemperatureUnit $unit = TemperatureUnit::CELSIUS): float
    {
        /** @var TemperatureSensorInterface $circuit */
        $circuit = &$this->circuit;
        $temp_c = $circuit->temperature();

        if (is_null($temp_c)) {
            throw new SensorException('Temperature reading is unavailable.');
        }

        return match ($unit) {
            TemperatureUnit::FAHRENHEIT => ($temp_c * (9 / 5)) + 32,
            TemperatureUnit::KELVIN => $temp_c + 273.15,
            default => $temp_c,
        };
    }

    public function measure(TemperatureUnit $unit = TemperatureUnit::CELSIUS): TemperatureSensorReading
    {
        $temp = $this->temperature($unit);

        return new TemperatureSensorReading($temp, $unit, strtotime('now'));
    }

    public static function as(IntegratedCircuit $circuit): static
    {
        $attr = reflect_class($circuit, MeasuresTemperature::class);
        if ($attr->getName() == MeasuresTemperature::class) {
            return new static($circuit);
        }

        throw SensorException::missingRequiredAbility('TemperatureSensor', $circuit::class, 'MeasuresTemperature');
    }
}
