<?php

namespace RealityInterface\Sensors\Applied\AmbientLighting;

use RealityInterface\Sensors\Attributes\MeasuresLuminance;
use RealityInterface\Sensors\Contracts\Applied\AmbientLighting\GenericLuxSensor;
use RealityInterface\Sensors\Enums\LuminanceUnit;
use RealityInterface\Sensors\Exceptions\SensorException;
use RealityInterface\Sensors\Sensor;
use RealityInterface\Sensors\SensorChip;

class AmbientLightSensor extends Sensor
{
    public LuminanceUnit $units = LuminanceUnit::LUX;

    public float $fc_compensation_factor = 1.0;

    public float $lux_compensation_factor = 1.0;

    public float $fc_conversion_divisor = 10.764;

    public function units(LuminanceUnit $units): static
    {
        $this->units = $units;

        return $this;
    }

    public function calibrate(float $lux_ref, float $lux_this): static
    {
        $this->fc_compensation_factor = ($lux_ref / $this->fc_conversion_divisor) / ($lux_this / $this->fc_conversion_divisor);
        $this->lux_compensation_factor = $lux_ref / $lux_this;

        return $this;
    }

    public function getLuminance(): int|float
    {
        /** @var GenericLuxSensor $sensor */
        $sensor = &$this->sensor;
        $measurement = $sensor->getLuminance();

        return match ($this->units) {
            LuminanceUnit::LUX => $measurement * $this->lux_compensation_factor,
            LuminanceUnit::FOOTCANDLE => ($measurement / $this->fc_conversion_divisor) * $this->fc_compensation_factor,
        };
    }

    public static function as(SensorChip $circuit): static
    {
        $attr = reflect_class($circuit, MeasuresLuminance::class);
        if ($attr->getName() == MeasuresLuminance::class) {
            return new static($circuit);
        }

        throw SensorException::missingRequiredAbility('LuxSensor', $circuit::class, 'MeasuresLuminance');
    }
}
