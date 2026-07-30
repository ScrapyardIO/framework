<?php

namespace Fabricate\Contracts\Actuation\Interfaces;

use Fabricate\Contracts\Circuits\IntegratedCircuit;

/**
 * Position-sensing potentiometer integrated circuit.
 *
 * Drivers expose the unprocessed analog reading and its normalized position.
 */
interface Potentiometer extends IntegratedCircuit
{
    public function raw(): int;

    public function position(): float;
}
