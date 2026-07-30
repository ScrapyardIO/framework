<?php

namespace Fabricate\Contracts\Actuation\HumanInput;

use Fabricate\Contracts\Circuits\IntegratedCircuit;

/**
 * Raw instantaneous digital button state supplied by a transport driver.
 */
interface ButtonInput extends IntegratedCircuit
{
    public function isDown(): bool;
}
