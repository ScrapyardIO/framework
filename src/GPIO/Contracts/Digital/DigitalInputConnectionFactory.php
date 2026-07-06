<?php

namespace GPIO\Contracts\Digital;

use GPIO\Contracts\Digital\DigitalPinConnectionFactory as DigitalPinConnectionFactoryContract;

interface DigitalInputConnectionFactory extends DigitalPinConnectionFactoryContract
{
    public function timeout(int $timeout_ms): static;
    public function lineBias(LineBias $line_bias): static;
    public function withEvents(bool $rising, bool $falling): static;
}
