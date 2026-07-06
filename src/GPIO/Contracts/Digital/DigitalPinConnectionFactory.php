<?php

namespace GPIO\Contracts\Digital;

use GPIO\Contracts\Common\GPIOConnectionFactory as GPIOConnectionFactoryContract;

interface DigitalPinConnectionFactory extends GPIOConnectionFactoryContract
{
    public function pin(int $value): static;
    public function name(string $value): static;
    public function device(int|string $value): static;
}
