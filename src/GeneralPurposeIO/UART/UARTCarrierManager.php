<?php

namespace GeneralPurposeIO\UART;

use Fabricate\Chassis\EntryNotFoundException;
use Fabricate\Contracts\Chassis\CircularDependencyException;
use Fabricate\NutsAndBolts\Manager;
use GeneralPurposeIO\Contracts\Common\CarrierDriverManager;
use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\UART\Factory\FtdiUARTDriverFactory;
use GeneralPurposeIO\UART\Factory\PosixUARTDriverFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class UARTCarrierManager extends Manager implements CarrierDriverManager
{
    /**
     * @throws GPIOException
     */
    public function createPosixDriver(): PosixUARTDriverFactory
    {
        if (extension_loaded('posi')) {
            if (function_exists('posix_open')) {
                if (function_exists('uart_open')) {
                    return new PosixUARTDriverFactory();
                }

                throw new GPIOException('The POSIX UART driver requires the UART package. Require it with composer require microscrap/uart');
            }

            throw new GPIOException('The POSIX driver requires the POSIX package. Require it with composer require microscrap/posix');
        }

        throw new GPIOException('The POSIX driver requires the ext-posi extension. Install it with pie install php-io-extension/posi');
    }

    /**
     * @throws GPIOException
     */
    public function createUsbDriver(): FtdiUARTDriverFactory
    {
        if (extension_loaded('ftdi')) {
            if (function_exists('ftdi_new')) {
                return new FtdiUARTDriverFactory();
            }

            throw new GPIOException('The USB UART driver requires the FTDI package. Require it with composer require microscrap/ftdi');
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
        return config('gpio.protocols.uart.default');
    }
}
