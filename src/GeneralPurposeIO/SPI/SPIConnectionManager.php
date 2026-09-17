<?php

namespace GeneralPurposeIO\SPI;

use Voyager\NutsAndBolts\Manager;

class SPIConnectionManager extends Manager
{
    public function createNoneDriver(): SPIConnectionDriver
    {
        return new NoneSPIConnectionDriver;
    }

    public function getDefaultDriver(): string
    {
        return $this->config->get('gpio.protocols.spi.default', 'none');
    }
}