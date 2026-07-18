<?php

namespace GeneralPurposeIO\I2C;

use Fabricate\Chassis\EntryNotFoundException;
use Fabricate\Contracts\Chassis\CircularDependencyException;
use Fabricate\NutsAndBolts\Manager;
use GeneralPurposeIO\Contracts\Common\CarrierDriverManager;
use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\I2C\Factory\MpsseI2CDriverFactory;
use GeneralPurposeIO\I2C\Factory\PosixI2CDriverFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class I2CCarrierManager extends Manager implements CarrierDriverManager
{
    /**
     * @throws GPIOException
     */
    public function createPosixDriver(): PosixI2CDriverFactory
    {
        if (extension_loaded('posi')) {
            if (function_exists('posix_open')) {
                if (function_exists('i2c_open')) {
                    return new PosixI2CDriverFactory();
                }

                throw new GPIOException('The POSIX I2C driver requires the I2C package. Require it with composer require microscrap/i2c');
            }

            throw new GPIOException('The POSIX driver requires the POSIX package. Require it with composer require microscrap/posix');
        }

        throw new GPIOException('The POSIX driver requires the ext-posi extension. Install it with pie install php-io-extension/posi');
    }

    /**
     * @throws GPIOException
     */
    public function createUsbDriver(): MpsseI2CDriverFactory
    {
        if (extension_loaded('ftdi')) {
            if (function_exists('mpsse_open')) {
                return new MpsseI2CDriverFactory();
            }

            throw new GPIOException('The USB I2C driver requires the MPSSE package. Require it with composer require microscrap/mpsse');
        }

        throw new GPIOException('The USB driver requires the ext-ftdi extension. Install it with pie install php-io-extension/ftdi');
    }

    public function adapter(?string $adapter = null)
    {
        return $this->driver($adapter);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws CircularDependencyException
     */
    public function getDefaultDriver()
    {
        return config('gpio.protocols.i2c.default');
    }
}
