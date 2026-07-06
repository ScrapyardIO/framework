<?php

namespace GPIO\Contracts\Common;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
readonly class CarrierDriver
{
    public function __construct(
        public string $driver
    ) {}
}
