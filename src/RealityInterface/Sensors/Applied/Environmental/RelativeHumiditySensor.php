<?php

namespace RealityInterface\Sensors\Applied\Environmental;

use RealityInterface\Sensors\Attributes\MeasuresRelativeHumidity;
use RealityInterface\Sensors\Contracts\Applied\Environmental\RHSensor;
use RealityInterface\Sensors\Exceptions\SensorException;
use RealityInterface\Sensors\SensorChip;

class RelativeHumiditySensor extends EnvironmentalSensor
{
    public function humidity(): float
    {
        /** @var RHSensor $sensor */
        $sensor = &$this->sensor;
        $humidity = $sensor->getHumidity();

        if (is_null($humidity)) {
            throw new SensorException('Relative humidity reading is unavailable.');
        }

        return $humidity;
    }

    public function measure(): RelativeHumidityReading
    {
        $percent = $this->humidity();

        return new RelativeHumidityReading($percent, strtotime('now'));
    }

    public static function as(SensorChip $circuit): static
    {
        $attr = reflect_class($circuit, MeasuresRelativeHumidity::class);
        if ($attr->getName() == MeasuresRelativeHumidity::class) {
            return new static($circuit);
        }

        throw SensorException::missingRequiredAbility('RelativeHumiditySensor', $circuit::class, 'MeasuresRelativeHumidity');
    }
}
