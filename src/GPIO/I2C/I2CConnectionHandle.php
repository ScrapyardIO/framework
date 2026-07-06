<?php

namespace GPIO\I2C;

use GPIO\Common\GPIOConnectionHandle;
use GPIO\Contracts\I2C\I2CConnectionHandle as I2CConnectionHandleContract;

abstract class I2CConnectionHandle extends GPIOConnectionHandle implements I2CConnectionHandleContract
{

}
