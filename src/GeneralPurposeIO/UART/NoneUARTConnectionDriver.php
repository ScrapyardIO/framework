<?php

namespace GeneralPurposeIO\UART;

use GeneralPurposeIO\Contracts\UART\UARTException;
use GeneralPurposeIO\Contracts\UART\UARTTransport;

/** The driver an app gets when no adapter package is configured: every open attempt says so. */
class NoneUARTConnectionDriver extends UARTConnectionDriver
{
    protected function newConnection(string $device): UARTConnectionFactory
    {
        throw UARTException::noDriverConfigured();
    }

    protected function getTransport(string $device): UARTTransport
    {
        throw UARTException::noDriverConfigured();
    }
}
