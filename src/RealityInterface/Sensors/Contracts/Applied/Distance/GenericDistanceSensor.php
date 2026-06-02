<?php

namespace RealityInterface\Sensors\Contracts\Applied\Distance;

interface GenericDistanceSensor
{
    public function getDistance(): int|float;
}
