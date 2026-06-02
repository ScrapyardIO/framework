<?php

namespace RealityInterface\Sensors\Contracts\Applied\Distance;

interface LaserGuidedDistanceSensor extends GenericDistanceSensor
{
    public function readDistance(): int|float;
}
