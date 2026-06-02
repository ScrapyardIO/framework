<?php

namespace RealityInterface\Sensors\Applied\Environmental;

use BareMetal\IntegratedCircuit;
use RealityInterface\Sensors\Attributes\MeasuresRelativeHumidity;
use RealityInterface\Sensors\Contracts\Applied\Environmental\RHSensor;
use RealityInterface\Sensors\Exceptions\SensorException;

class RelativeHumiditySensor extends EnvironmentalSensor
{
    public function humidity(): float
    {
        /** @var RHSensor $circuit */
        $circuit = &$this->circuit;
        $humidity = $circuit->humidity();

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

    public static function as(IntegratedCircuit $circuit): static
    {
        $attr = reflect_class($circuit, MeasuresRelativeHumidity::class);
        if ($attr->getName() == MeasuresRelativeHumidity::class) {
            return new static($circuit);
        }

        throw SensorException::missingRequiredAbility('RelativeHumiditySensor', $circuit::class, 'MeasuresRelativeHumidity');
    }
}
