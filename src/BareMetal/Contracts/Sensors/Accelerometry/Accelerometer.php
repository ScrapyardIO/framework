<?php

namespace BareMetal\Contracts\Sensors\Accelerometry;

interface Accelerometer
{
    public function hasAxis(SpatialAxis $axis): bool;
    public function enable(): void;
    public function disable(): void;

    public function getPitch(): float;
    public function getRoll(): float;
    public function getX(): float;
    public function getY(): float;
    public function getZ(): float;
    public function getAcceleration(): float;
    public function getInclination(): float;
    public function getOrientation(): AxisOrientation;
}
