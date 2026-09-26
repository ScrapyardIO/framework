<?php

namespace ScrapyardIO\Tests\Fixtures;

use GeneralPurposeIO\UART\UARTConnectionFactory;

final class FakeUARTConnectionFactory extends UARTConnectionFactory
{
    protected function device(): string
    {
        return $this->device;
    }

    protected function getHandle(): FakeUARTHandle
    {
        return new FakeUARTHandle($this->device, $this->baud_rate);
    }
}
