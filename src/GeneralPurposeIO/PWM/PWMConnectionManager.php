<?php

namespace GeneralPurposeIO\PWM;

use Voyager\NutsAndBolts\Manager;

class PWMConnectionManager extends Manager
{
    public function createNoneDriver(): PWMConnectionDriver
    {
        return new NonePWMConnectionDriver;
    }

    public function getDefaultDriver(): string
    {
        return $this->config->get('gpio.protocols.pwm.default', 'none');
    }
}
