<?php

namespace Fabricate\Actuation;

use Fabricate\Contracts\Circuits\IntegratedCircuit;
use Fabricate\Contracts\Actuation\Actuator as ActuatorContract;

abstract class Actuator implements ActuatorContract
{
    public function __construct(
        protected readonly IntegratedCircuit $circuit,
    ) {}

    abstract public static function circuit(string $driver): static;
}