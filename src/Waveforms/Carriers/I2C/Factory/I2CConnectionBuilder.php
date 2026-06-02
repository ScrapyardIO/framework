<?php

namespace Waveforms\Carriers\I2C\Factory;

use Waveforms\Carriers\I2C\I2CDevice;
use Waveforms\Factory\CarrierFactory;

abstract class I2CConnectionBuilder extends CarrierFactory
{
    public ?int $slave_address = null;

    abstract public function firstly(int|string $chip_device): static;

    abstract public function boot(): I2CDevice;

    public function slaveAddress(int $address): static
    {
        $this->slave_address = $address;

        return $this;
    }
}
