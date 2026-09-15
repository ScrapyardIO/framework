<?php

namespace GeneralPurposeIO\Analog;

use GeneralPurposeIO\Contracts\Common\GPIOException;
use InvalidArgumentException;
use Voyager\NutsAndBolts\Manager;
use Voyager\Contracts\Vessel\CircularDependencyException;
use GeneralPurposeIO\Contracts\Analog\AnalogOutputCommunicationAdapter as AdapterInterface;
use GeneralPurposeIO\Contracts\Common\GPIOCommunicationAdapterManager as AdapterManager;

class DACAdapterManager extends Manager implements AdapterManager
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
            throw new GPIOException("DAC Adapter [$adapter] not registered. Is it defined in config(gpio.protocols.analog-out.adapters)?");
        }
    }
    /**
     * @throws CircularDependencyException
     */
    public function getDefaultDriver(): ?string
    {
        return config('gpio.protocols.analog-out.default');
    }
}