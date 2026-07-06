<?php

namespace GPIO\Contracts\SPI;

use GPIO\Contracts\Common\GPIOConnectionHandle;
use GPIO\Contracts\Digital\DigitalPinConnectionHandle;

interface SPIConnectionHandle extends GPIOConnectionHandle, DigitalPinConnectionHandle
{

}
