<?php

namespace GeneralPurposeIO\PWM\Drivers;

use GeneralPurposeIO\Contracts\PWM\PWMDriver as DriverContract;
use GeneralPurposeIO\Contracts\PWM\PWMPolarity;

abstract class PWMDriver implements DriverContract
{
    abstract public function close(int $channel): void;

    abstract public function setDutyCycle(int $channel, int $value): int;

    abstract public function getDutyCycle(int $channel): int;

    abstract public function setPeriod(int $channel, int $value): int;

    abstract public function getPeriod(int $channel): int;

    abstract public function setEnable(int $channel, bool $value): bool;

    abstract public function getEnable(int $channel): bool;

    abstract public function setPolarity(int $channel, PWMPolarity $value): PWMPolarity;

    abstract public function getPolarity(int $channel): PWMPolarity;
}