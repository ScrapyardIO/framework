<?php

namespace RealityInterface\Sensors;

use BareMetal\IntegratedCircuit;
use BareMetal\Repositories\IntegratedCircuitRepository;

abstract class Sensor
{
    public function __construct(
        protected IntegratedCircuit $circuit,
    ) {}

    public static function using(string $circuit_name): static
    {
        $circuit = IntegratedCircuitRepository::circuit($circuit_name);

        return static::as($circuit);
    }

    abstract public static function as(IntegratedCircuit $circuit): static;

    public function integratedCircuit(): IntegratedCircuit
    {
        return $this->circuit;
    }
}
