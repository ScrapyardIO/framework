<?php

namespace GPIO\Contracts\I2C;

use GPIO\Contracts\Common\GPIOConnectionFactory as GPIOConnectionFactoryContract;

interface I2CConnectionFactory extends GPIOConnectionFactoryContract
{
    public function slave(int $value): static;
    public function device(string|int $value): static;
}
