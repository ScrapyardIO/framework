<?php

namespace GPIO\SPI;

use GPIO\Common\GPIOConnectionHandle;
use GPIO\Contracts\SPI\SPIConnectionHandle as SPIConnectionHandleContract;

abstract class SPIConnectionHandle extends GPIOConnectionHandle implements SPIConnectionHandleContract
{

}
