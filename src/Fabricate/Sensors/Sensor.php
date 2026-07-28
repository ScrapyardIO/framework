<?php

namespace Fabricate\Sensors;

use Fabricate\Contracts\Circuits\IntegratedCircuit;
use Fabricate\Contracts\Sensors\Sensor as SensorContract;

abstract class Sensor implements SensorContract
{
    public function __construct(
        protected readonly IntegratedCircuit $circuit,
    ) {}

    abstract public static function circuit(string $driver): static;
}