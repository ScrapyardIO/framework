<?php

namespace GeneralPurposeIO\SPI\Drivers;

use GeneralPurposeIO\Contracts\SPI\SPIDriver as DriverContract;

abstract class SPIDriver implements DriverContract
{
    abstract public function close(): void;
}