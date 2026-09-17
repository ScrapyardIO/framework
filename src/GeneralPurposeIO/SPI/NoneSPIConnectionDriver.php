<?php

namespace GeneralPurposeIO\SPI;

use GeneralPurposeIO\Contracts\SPI\SPIException;
use GeneralPurposeIO\Contracts\SPI\SPITransport;

/** The driver an app gets when no adapter package is configured: every open attempt says so. */
class NoneSPIConnectionDriver extends SPIConnectionDriver
{
    protected function newConnection(int|string $device): SPIConnectionFactory
    {
        throw SPIException::noDriverConfigured();
    }

    protected function getTransport(int|string $device, int $chip_select): SPITransport
    {
        throw SPIException::noDriverConfigured();
    }
}
