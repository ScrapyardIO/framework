<?php

namespace RealityInterface\Sensors\Contracts\Applied\Environmental;

interface TemperatureSensor extends GenericEnvironmentalSensor
{
    public function temperature(): ?float;
}
