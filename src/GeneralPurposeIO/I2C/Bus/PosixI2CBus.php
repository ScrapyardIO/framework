<?php

namespace GeneralPurposeIO\I2C\Bus;

use Microscrap\Bindings\I2C\Bus;
use GeneralPurposeIO\I2C\I2CSlave;
use GeneralPurposeIO\I2C\Drivers\PosixI2CDriver;
use GeneralPurposeIO\Contracts\I2C\I2CException;
use Microscrap\Bindings\I2C\DataObjects\I2CBus as I2CHandle;

class PosixI2CBus extends I2CBus
{
    public function __construct(
        protected PosixI2CDriver $driver
    ) {}

    /**
     * @throws I2CException
     */
    public function slave(int $address): I2CSlave|false
    {
        $results = false;

        if (($address > 0x07) && ($address <= 0x77)) {
            $bus = new I2CHandle($this->driver->fd, "", 0x00);
            if(Bus::i2cSetSlaveAddr($bus, $address) !== 0)
            {
                throw new I2CException("Slave {$address} not available.");
            };

            $results = new I2CSlave($address, $this->driver);
        }

        return $results;
    }

    public function canServeDigitalPins(): bool
    {
        return false;
    }
}