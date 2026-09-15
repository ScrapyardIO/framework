<?php

namespace GeneralPurposeIO\SPI\Bus;

abstract class SPIBus
{
    abstract public function canServeDigitalPins(): bool;
}