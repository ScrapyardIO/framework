<?php

namespace GeneralPurposeIO\UART\Bus;

use GeneralPurposeIO\UART\Drivers\PosixUARTDriver;

class PosixUARTBus extends UARTBus
{
    public function __construct(PosixUARTDriver $driver)
    {
        parent::__construct($driver);
    }
}
