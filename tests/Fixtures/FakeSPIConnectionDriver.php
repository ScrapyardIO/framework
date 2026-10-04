<?php

namespace ScrapyardIO\Tests\Fixtures;

use GeneralPurposeIO\SPI\SPIConnectionDriver;
use GeneralPurposeIO\SPI\SPIConnectionFactory;

class FakeSPIConnectionDriver extends SPIConnectionDriver
{
    protected function newConnection(int|string $device): SPIConnectionFactory
    {
        return new FakeSPIConnectionFactory($device, $this);
    }

    protected function getTransport(int|string $device, int $chip_select): FakeSPITransport
    {
        return new FakeSPITransport($chip_select, $this->connections->get($device));
    }

    /** @param FakeSPIHandle $handle */
    protected function closeConnection(mixed $handle): void
    {
        $handle->closed = true;
    }
}
