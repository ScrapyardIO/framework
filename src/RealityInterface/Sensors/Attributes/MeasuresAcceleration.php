<?php

namespace RealityInterface\Sensors\Attributes;

use Attribute;
use RealityInterface\Sensors\Enums\SensorType;

#[Attribute(Attribute::TARGET_CLASS)]
class MeasuresAcceleration
{
    public function __construct(
        public readonly SensorType $sensor_type
    ) {}
}
