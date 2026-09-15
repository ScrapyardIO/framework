<?php

namespace GeneralPurposeIO\PWM\Factory;

use GeneralPurposeIO\Contracts\PWM\PWMDriver;

abstract class PWMFactory
{
    abstract protected function assertReady(): void;
    abstract public function driver(): PWMDriver;
}