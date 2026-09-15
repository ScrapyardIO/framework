<?php

namespace GeneralPurposeIO\I2C\Bus;

abstract class I2CBus
{
    abstract public function canServeDigitalPins(): bool;
}