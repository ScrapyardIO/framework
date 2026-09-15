<?php

namespace GeneralPurposeIO\SPI;

use Voyager\NutsAndBolts\Manager;
use Voyager\Contracts\Vessel\CircularDependencyException;
use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\Contracts\SPI\SPICommunicationAdapter as AdapterInterface;
use GeneralPurposeIO\Contracts\Common\GPIOCommunicationAdapterManager as AdapterManager;
use InvalidArgumentException;

class SPIAdapterManager extends Manager implements AdapterManager
{
    /**
     * @throws GPIOException
     */
    public function adapter(?string $adapter = null): AdapterInterface
    {
        try {
            return $this->driver($adapter);
        }
        catch (InvalidArgumentException)
        {
            throw new GPIOException("SPI Adapter [$adapter] not registered. Is it defined in config(gpio.protocols.spi.adapters)?");
        }
    }
    /**
     * @throws CircularDependencyException
     */
    public function getDefaultDriver(): ?string
    {
        return config('gpio.protocols.spi.default');
    }
}