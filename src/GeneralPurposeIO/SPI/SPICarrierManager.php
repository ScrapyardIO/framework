<?php

namespace GeneralPurposeIO\SPI;

use Fabricate\Chassis\EntryNotFoundException;
use Fabricate\Contracts\Chassis\CircularDependencyException;
use Fabricate\NutsAndBolts\Manager;
use GeneralPurposeIO\Contracts\Common\CarrierDriverManager;
use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\SPI\Factory\MpsseSPIDriverFactory;
use GeneralPurposeIO\SPI\Factory\PosixSPIDriverFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SPICarrierManager extends Manager implements CarrierDriverManager
{
    /**
     * @throws GPIOException
     */
    public function createPosixDriver(): PosixSPIDriverFactory
    {
        if (extension_loaded('posi')) {
            if (function_exists('posix_open')) {
                if (function_exists('spi_open')) {
                    return new PosixSPIDriverFactory();
                }

                throw new GPIOException('The POSIX SPI driver requires the SPI package. Require it with composer require microscrap/spi');
            }

            throw new GPIOException('The POSIX driver requires the POSIX package. Require it with composer require microscrap/posix');
        }

        throw new GPIOException('The POSIX driver requires the ext-posi extension. Install it with pie install php-io-extension/posi');
    }

    /**
     * @throws GPIOException
     */
    public function createUsbDriver(): MpsseSPIDriverFactory
    {
        if (extension_loaded('ftdi')) {
            if (function_exists('mpsse_open')) {
                return new MpsseSPIDriverFactory();
            }

            throw new GPIOException('The USB SPI driver requires the MPSSE package. Require it with composer require microscrap/mpsse');
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
        return config('gpio.protocols.spi.default');
    }
}
