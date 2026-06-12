<?php

namespace RealityInterface\Sensors\Exceptions;

use BareMetal\Exceptions\CircuitRepoException;

class SensorRepoException extends CircuitRepoException
{
    public static function sensorChipNotRegistered($circuit_name): static
    {
        return new static("Sensor '$circuit_name' is not registered");
    }
}
