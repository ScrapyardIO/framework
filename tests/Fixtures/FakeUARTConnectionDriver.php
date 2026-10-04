<?php

namespace ScrapyardIO\Tests\Fixtures;

use GeneralPurposeIO\UART\UARTConnectionDriver;
use GeneralPurposeIO\UART\UARTConnectionFactory;

class FakeUARTConnectionDriver extends UARTConnectionDriver
{
    protected function newConnection(string $device): UARTConnectionFactory
    {
        return new FakeUARTConnectionFactory($device, $this);
    }

    protected function getTransport(string $device): FakeUARTTransport
    {
        /** @var FakeUARTHandle $handle */
        $handle = $this->connections->get($device);

        return new FakeUARTTransport($device, $handle->baud, $handle);
    }

    /** @param FakeUARTHandle $handle */
    protected function closeConnection(mixed $handle): void
    {
        $handle->closed = true;
    }
}
