<?php

namespace GeneralPurposeIO\Circuits;

use GeneralPurposeIO\Contracts\Circuits\IntegratedCircuit as CircuitContract;

abstract class IntegratedCircuit implements CircuitContract
{
    abstract public function close(): void;
}
