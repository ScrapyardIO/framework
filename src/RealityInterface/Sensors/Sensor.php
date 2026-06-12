<?php

namespace RealityInterface\Sensors;

use RealityInterface\Sensors\Repositories\SensorChipRepository;

abstract class Sensor
{
    public function __construct(
        protected SensorChip $sensor,
    ) {}

    public static function using(string $circuit_name): static
    {
        $circuit = SensorChipRepository::sensor($circuit_name);

        return static::as($circuit);
    }

    abstract public static function as(SensorChip $circuit): static;

    public function sensorChip(): SensorChip
    {
        return $this->sensor;
    }
}
