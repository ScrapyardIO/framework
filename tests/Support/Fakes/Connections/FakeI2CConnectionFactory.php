<?php

namespace ScrapyardIO\Tests\Support\Fakes\Connections;

use GeneralPurposeIO\I2C\I2CConnectionFactory;

class FakeI2CConnectionFactory extends I2CConnectionFactory
{
    protected function device(): string|int
    {
        return $this->device;
    }

    protected function getHandle(): FakeHandle
    {
        return new FakeHandle($this->device);
    }
}
