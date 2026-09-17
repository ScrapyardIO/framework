<?php

namespace GeneralPurposeIO\Digital;

use Voyager\NutsAndBolts\Manager;

class DigitalOConnectionManager extends Manager
{
    public function createNoneDriver(): DigitalIOConnectionDriver
    {
        return new NoneDigitalIOConnectionDriver;
    }

    public function getDefaultDriver(): string
    {
        return $this->config->get('gpio.protocols.digital-in.default', 'none');
    }
}
