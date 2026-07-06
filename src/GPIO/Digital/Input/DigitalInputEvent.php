<?php

namespace GPIO\Digital\Input;

use GPIO\Contracts\Digital\DigitalInputEvent as DigitalInputEventContract;
use GPIO\Contracts\Digital\EdgeEvent;

readonly class DigitalInputEvent implements DigitalInputEventContract
{
    public function __construct(
        public EdgeEvent $event,
        public int|float $timestamp,
    ) {}
}
