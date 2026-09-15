<?php

namespace GeneralPurposeIO\I2C\Factory;

use GeneralPurposeIO\Contracts\I2C\I2CDriver;

abstract class I2CFactory
{
    abstract protected function assertReady(): void;
    abstract public function driver(): I2CDriver;
}