<?php

namespace GPIO\Contracts\PWM;

use GPIO\Contracts\Common\GPIOConnectionFactory as GPIOConnectionFactoryContract;

interface PWMConnectionFactory extends GPIOConnectionFactoryContract
{
    public function name(string $value): static;
    public function channel(int $value): static;
    public function device(int|string $value): static;
}
