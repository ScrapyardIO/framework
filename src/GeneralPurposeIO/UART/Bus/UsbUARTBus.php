<?php

namespace GeneralPurposeIO\UART\Bus;

use GeneralPurposeIO\UART\Drivers\UsbUARTDriver;

class UsbUARTBus extends UARTBus
{
    public function __construct(UsbUARTDriver $driver)
    {
        parent::__construct($driver);
    }
}
