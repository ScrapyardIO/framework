<?php

namespace ScrapyardIO\Tests\Support\Fakes\Connections;

use GeneralPurposeIO\UART\UARTConnectionFactory;

class FakeUARTConnectionFactory extends UARTConnectionFactory
{
    protected function device(): string
    {
        return $this->device;
    }

    protected function getHandle(): FakeHandle
    {
        return new FakeHandle($this->device);
    }
}
