<?php

namespace GeneralPurposeIO\I2C\Adapters;

use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\Contracts\I2C\I2CException;
use GeneralPurposeIO\Contracts\SPI\SPIException;
use GeneralPurposeIO\I2C\Factory\PosixI2CFactory;
use GeneralPurposeIO\I2C\I2CCommunicationAdapter;
use GeneralPurposeIO\Common\ConfirmPOSIXDependencies;

class PosixI2CAdapter extends I2CCommunicationAdapter
{
    /**
     * @throws I2CException
     */
    public function device(int $device): PosixI2CFactory
    {
        if(!file_exists("/dev/i2c-{$device}"))
        {
            throw new I2CException("Device {$device} does not exist");
        }

        return new PosixI2CFactory($device);
    }

    protected function confirmDependencies(): void
    {
        ConfirmPOSIXDependencies::run('I2C');

        if (!function_exists('i2c_open')) {
            throw new GPIOException('The POSIX I2C adapter requires the I2C package. Require it with composer require microscrap/i2c');
        }
    }
}