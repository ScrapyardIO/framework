<?php

namespace RealityInterface\Sensors\Contracts\Applied\Environmental;

interface RHSensor extends GenericEnvironmentalSensor
{
    public function humidity(): ?float;
}
