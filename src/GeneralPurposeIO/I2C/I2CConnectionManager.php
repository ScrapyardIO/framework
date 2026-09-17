<?php

namespace GeneralPurposeIO\I2C;

use Voyager\NutsAndBolts\Manager;

class I2CConnectionManager extends Manager
{
    public function createNoneDriver(): I2CConnectionDriver
    {
        return new NoneI2CConnectionDriver;
    }

    public function getDefaultDriver(): string
    {
        return $this->config->get('gpio.protocols.i2c.default', 'none');
    }
}