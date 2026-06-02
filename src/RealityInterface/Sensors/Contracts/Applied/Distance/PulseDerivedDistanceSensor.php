<?php

namespace RealityInterface\Sensors\Contracts\Applied\Distance;

interface PulseDerivedDistanceSensor extends GenericDistanceSensor
{
    public function firePulse(): void;

    public function readDistance(): int|float;
}
