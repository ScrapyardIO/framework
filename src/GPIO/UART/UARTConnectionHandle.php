<?php

namespace GPIO\UART;

use GPIO\Common\GPIOConnectionHandle;
use GPIO\Contracts\UART\UARTConnectionHandle as UARTConnectionHandleContract;

abstract class UARTConnectionHandle extends GPIOConnectionHandle implements UARTConnectionHandleContract
{

}
