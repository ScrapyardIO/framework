<?php

namespace RealityInterface\Sensors\Contracts\Applied\Environmental;

interface RHSensor extends GenericEnvironmentalSensor
{
    public function getHumidity(): ?float;
}
