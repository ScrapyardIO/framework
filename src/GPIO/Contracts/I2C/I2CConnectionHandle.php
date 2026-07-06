<?php

namespace GPIO\Contracts\I2C;

use GPIO\Contracts\Common\GPIOConnectionHandle;
use GPIO\Contracts\Digital\DigitalPinConnectionHandle;

interface I2CConnectionHandle extends GPIOConnectionHandle, DigitalPinConnectionHandle
{
    public function slaveAddress(): int;
}
