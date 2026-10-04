<?php

namespace ScrapyardIO\Tests\Fixtures;

use GeneralPurposeIO\I2C\I2CConnectionFactory;

final class FakeI2CConnectionFactory extends I2CConnectionFactory
{
    protected function device(): string|int
    {
        return $this->device;
    }

    protected function getHandle(): FakeI2CHandle
    {
        return new FakeI2CHandle($this->device);
    }
}
