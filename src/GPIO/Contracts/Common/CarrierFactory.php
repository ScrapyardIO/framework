<?php

namespace GPIO\Contracts\Common;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
readonly class CarrierFactory
{
    public function __construct(
        public string $protocol
    ) {}
}
