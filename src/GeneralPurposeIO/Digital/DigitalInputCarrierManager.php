<?php

namespace GeneralPurposeIO\Digital;

use Fabricate\NutsAndBolts\Manager;
use Fabricate\Chassis\EntryNotFoundException;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\Contracts\Common\CarrierDriverManager;
use Fabricate\Contracts\Chassis\CircularDependencyException;
use GeneralPurposeIO\Digital\Factory\MpsseDigitalInputDriverFactory;
use GeneralPurposeIO\Digital\Factory\PosixDigitalInputDriverFactory;

class DigitalInputCarrierManager extends Manager implements CarrierDriverManager
{
    /**
     * @return PosixDigitalInputDriverFactory
     * @throws GPIOException
     */
    public function createPosixDriver(): PosixDigitalInputDriverFactory
    {
        if(extension_loaded('posi'))
        {
            if(function_exists('posix_open'))
            {
                if(function_exists('gpiod_chip_open'))
                {
                    return new PosixDigitalInputDriverFactory();
                }
                throw new GPIOException("The POSIX driver requires the GPIO package. Require it with composer require microscrap/gpio");
            }
            throw new GPIOException("The POSIX driver requires the POSIX package. Require it with composer require microscrap/posix");
        }
        throw new GPIOException("The POSIX driver requires the ext-posi extension. Install it with pie install php-io-extension/posi");
    }

    /**
     * @return MpsseDigitalInputDriverFactory
     * @throws GPIOException
     */
    public function createUsbDriver(): MpsseDigitalInputDriverFactory
    {
        if(extension_loaded('ftdi'))
        {
            if(function_exists('mpsse_open'))
            {
                return new MpsseDigitalInputDriverFactory();
            }

            throw new GPIOException("The USB driver requires the MPSSE package. Require it with composer require microscrap/mpsse");
        }

        throw new GPIOException("The USB driver requires the FTDI extension. Install it with pie install php-io-extension/ftdi");
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
        return config('gpio.protocols.digital-in.default');
    }
}
