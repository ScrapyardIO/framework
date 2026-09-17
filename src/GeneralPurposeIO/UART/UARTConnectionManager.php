<?php

namespace GeneralPurposeIO\UART;

use Voyager\NutsAndBolts\Manager;

class UARTConnectionManager extends Manager
{
    public function createNoneDriver(): UARTConnectionDriver
    {
        return new NoneUARTConnectionDriver;
    }

    public function getDefaultDriver(): string
    {
        return $this->config->get('gpio.protocols.uart.default', 'none');
    }
}