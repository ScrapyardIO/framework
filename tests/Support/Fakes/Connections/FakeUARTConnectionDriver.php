<?php

namespace ScrapyardIO\Tests\Support\Fakes\Connections;

use GeneralPurposeIO\Contracts\UART\UARTTransport;
use GeneralPurposeIO\UART\UARTConnectionDriver;
use GeneralPurposeIO\UART\UARTConnectionFactory;

class FakeUARTConnectionDriver extends UARTConnectionDriver
{
    protected function newConnection(string $device): UARTConnectionFactory
    {
        return new FakeUARTConnectionFactory($device, $this);
    }

    protected function getTransport(string $device): UARTTransport
    {
        return new FakeUARTTransport($device, $this->connections->get($device));
    }
}
