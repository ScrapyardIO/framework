<?php

namespace GPIO\Contracts\PWM;

interface PWMChannelAPI
{
    public function setDutyCycle(int $value): int;
    public function getDutyCycle(): int;

    public function setPeriod(int $value): int;
    public function getPeriod(): int;

    public function setEnable(bool $value): bool;
    public function getEnable(): bool;

    public function setPolarity(string $value): string;
    public function getPolarity(): string;
}
