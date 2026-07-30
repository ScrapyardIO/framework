<?php

namespace Fabricate\Contracts\Actuation\Interfaces;

use Fabricate\Contracts\Circuits\IntegratedCircuit;

/**
 * Acceleration-capable integrated circuit (ADXL, etc.).
 *
 * Drivers report a fresh sample for each axis in g (or the driver's native
 * acceleration unit). Multi-capability chips may implement this alongside
 * other sensor interfaces.
 */
interface LED extends IntegratedCircuit
{

}
