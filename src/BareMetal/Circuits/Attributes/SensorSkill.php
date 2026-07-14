<?php

namespace BareMetal\Circuits\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class SensorSkill
{
    public function __construct(
        public string|array $implements,
    ) {}
}
