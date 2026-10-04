<?php

namespace ScrapyardIO\Tests\Fixtures;

use GeneralPurposeIO\I2C\I2CConnectionDriver;
use GeneralPurposeIO\I2C\I2CConnectionFactory;

class FakeI2CConnectionDriver extends I2CConnectionDriver
{
    protected function newConnection(int|string $device): I2CConnectionFactory
    {
        return new FakeI2CConnectionFactory($device, $this);
    }

    protected function getTransport(string|int $device, int $slave_address): FakeI2CTransport
    {
        return new FakeI2CTransport($slave_address, $this->connections->get($device));
    }

    /** @param FakeI2CHandle $handle */
    protected function closeConnection(mixed $handle): void
    {
        $handle->closed = true;
    }
}
