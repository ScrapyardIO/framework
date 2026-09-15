<?php

namespace GeneralPurposeIO\Digital;

use InvalidArgumentException;
use Voyager\NutsAndBolts\Manager;
use Voyager\Contracts\Vessel\CircularDependencyException;
use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\Contracts\Common\GPIOCommunicationAdapterManager as AdapterManager;
use GeneralPurposeIO\Contracts\Digital\DigitalIOCommunicationAdapter as AdapterInterface;

class DigitalIOAdapterManager extends Manager implements AdapterManager
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
            throw new GPIOException("DigitalIO Adapter [$adapter] not registered. Is it defined in config(gpio.protocols.digital-io.adapters)?");
        }
    }

    /**
     * @throws CircularDependencyException
     */
    public function getDefaultDriver(): ?string
    {
        return config('gpio.protocols.digital-io.default');
    }
}