<?php

namespace GeneralPurposeIO\I2C;

use GeneralPurposeIO\Contracts\I2C\I2CException;
use GeneralPurposeIO\Contracts\I2C\I2CTransport;

/** The driver an app gets when no adapter package is configured: every open attempt says so. */
class NoneI2CConnectionDriver extends I2CConnectionDriver
{
    protected function newConnection(int|string $device): I2CConnectionFactory
    {
        throw I2CException::noDriverConfigured();
    }

    protected function getTransport(string|int $device, int $slave_address): I2CTransport
    {
        throw I2CException::noDriverConfigured();
    }
}
