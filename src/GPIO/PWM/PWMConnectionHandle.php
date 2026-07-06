<?php

namespace GPIO\PWM;

use GPIO\Common\GPIOConnectionHandle;
use GPIO\Contracts\PWM\PWMConnectionHandle as PWMConnectionHandleContract;

abstract class PWMConnectionHandle extends GPIOConnectionHandle implements PWMConnectionHandleContract
{

}
