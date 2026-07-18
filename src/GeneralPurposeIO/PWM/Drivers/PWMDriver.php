<?php

namespace GeneralPurposeIO\PWM\Drivers;

use GeneralPurposeIO\Contracts\PWM\PWMPolarity;

abstract class PWMDriver
{
    abstract public function close(): void;

    abstract public function setDutyCycle(int $value): int;

    abstract public function getDutyCycle(): int;

    abstract public function setPeriod(int $value): int;

    abstract public function getPeriod(): int;

    abstract public function setEnable(bool $value): bool;

    abstract public function getEnable(): bool;

    abstract public function setPolarity(PWMPolarity $value): PWMPolarity;

    abstract public function getPolarity(): PWMPolarity;
}
