<?php

namespace ScrapyardIO\Tests\Support\Fakes\Connections;

use GeneralPurposeIO\I2C\I2CConnectionDriver;
use GeneralPurposeIO\I2C\I2CConnectionFactory;
use GeneralPurposeIO\I2C\I2CTransport;

class FakeI2CConnectionDriver extends I2CConnectionDriver
{
    protected function newConnection(int|string $device): I2CConnectionFactory
    {
        return new FakeI2CConnectionFactory($device, $this);
    }

    protected function getTransport(string|int $device, int $slave_address): I2CTransport
    {
        return new FakeI2CTransport($slave_address, $this->connections->get($device));
    }
}
