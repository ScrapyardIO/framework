<?php

namespace GeneralPurposeIO\UART;

use GeneralPurposeIO\Contracts\UART\UARTException;

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

    /** newConnection() never succeeds, so there is never a handle to close. */
    protected function closeConnection(mixed $handle): void {}
}
