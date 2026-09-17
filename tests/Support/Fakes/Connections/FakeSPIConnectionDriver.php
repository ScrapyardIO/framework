<?php

namespace ScrapyardIO\Tests\Support\Fakes\Connections;

use GeneralPurposeIO\Contracts\SPI\SPITransport;
use GeneralPurposeIO\SPI\SPIConnectionDriver;
use GeneralPurposeIO\SPI\SPIConnectionFactory;

class FakeSPIConnectionDriver extends SPIConnectionDriver
{
    protected function newConnection(int|string $device): SPIConnectionFactory
    {
        return new FakeSPIConnectionFactory($device, $this);
    }

    protected function getTransport(int|string $device, int $chip_select): SPITransport
    {
        return new FakeSPITransport($chip_select, $this->connections->get($device));
    }
}
