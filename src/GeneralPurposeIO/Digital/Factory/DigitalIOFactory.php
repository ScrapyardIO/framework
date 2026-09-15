<?php

namespace GeneralPurposeIO\Digital\Factory;

use GeneralPurposeIO\Digital\Drivers\DigitalIODriver;

abstract class DigitalIOFactory
{
    abstract protected function assertReady(): void;
    abstract public function driver(): DigitalIODriver;
}