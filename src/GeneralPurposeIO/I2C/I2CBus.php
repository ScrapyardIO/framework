<?php

namespace GeneralPurposeIO\I2C;

use GeneralPurposeIO\Contracts\I2C\I2CDriver as I2CDriverInterface;

class I2CBus
{
    public function __construct(
        protected I2CDriverInterface $driver,
    ) {}
}