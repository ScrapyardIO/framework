<?php

namespace Fabricate\Contracts\Sensors\Interfaces;

use Fabricate\Contracts\Circuits\IntegratedCircuit;

/**
 * Acceleration-capable integrated circuit (ADXL, etc.).
 *
 * Drivers report a fresh sample for each axis in g (or the driver's native
 * acceleration unit). Multi-capability chips may implement this alongside
 * other sensor interfaces.
 */
interface Accelerometer extends IntegratedCircuit
{
    /**
     * Measure acceleration along the X axis.
     */
    public function x(): float;

    /**
     * Measure acceleration along the Y axis.
     */
    public function y(): float;

    /**
     * Measure acceleration along the Z axis.
     */
    public function z(): float;
}
