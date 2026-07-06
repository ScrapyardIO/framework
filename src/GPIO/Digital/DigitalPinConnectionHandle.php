<?php

namespace GPIO\Digital;

use GPIO\Common\GPIOConnectionHandle;

use GPIO\Contracts\Digital\DigitalPinConnectionHandle as DigitalPinConnectionHandleContract;

abstract class DigitalPinConnectionHandle extends GPIOConnectionHandle implements DigitalPinConnectionHandleContract
{

}
