<?php

namespace RealityInterface\Sensors\Contracts\Applied\Environmental;

interface PressureSensor extends GenericEnvironmentalSensor
{
    public function pressure(): ?float;
}
