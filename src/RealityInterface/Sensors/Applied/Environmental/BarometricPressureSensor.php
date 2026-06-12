<?php

namespace RealityInterface\Sensors\Applied\Environmental;

use RealityInterface\Sensors\Attributes\MeasuresBarometricPressure;
use RealityInterface\Sensors\Contracts\Applied\Environmental\PressureSensor;
use RealityInterface\Sensors\Enums\PressureUnit;
use RealityInterface\Sensors\Exceptions\SensorException;
use RealityInterface\Sensors\SensorChip;

class BarometricPressureSensor extends EnvironmentalSensor
{
    public function pressure(PressureUnit $unit = PressureUnit::PA): float
    {
        /** @var PressureSensor $sensor */
        $sensor = &$this->sensor;
        $pressure_pa = $sensor->getPressure();

        if (is_null($pressure_pa)) {
            throw new SensorException('Barometric pressure reading is unavailable.');
        }

        return match ($unit) {
            PressureUnit::HPA, PressureUnit::MBAR => $pressure_pa / 100.0,
            PressureUnit::ATM => $pressure_pa / 101_325.0,
            PressureUnit::MM_MERCURY => $pressure_pa / 133.322,
            PressureUnit::PSI => $pressure_pa / 6_894.757293168,
            default => $pressure_pa,
        };
    }

    public function measure(PressureUnit $unit = PressureUnit::PA): BarometricPressureReading
    {
        $pressure = $this->pressure($unit);

        return new BarometricPressureReading($pressure, $unit, strtotime('now'));
    }

    public static function as(SensorChip $circuit): static
    {
        $attr = reflect_class($circuit, MeasuresBarometricPressure::class);
        if ($attr->getName() == MeasuresBarometricPressure::class) {
            return new static($circuit);
        }

        throw SensorException::missingRequiredAbility('BarometricPressureSensor', $circuit::class, 'MeasuresBarometricPressure');
    }
}
