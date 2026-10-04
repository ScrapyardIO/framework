<?php

namespace GeneralPurposeIO\Contracts\PWM;

use GeneralPurposeIO\Contracts\NutsAndBolts\GPIOTransport;

interface PWMTransport extends GPIOTransport
{
    public function getPeriod(): int;
    public function setPeriod(int $value): int;

    public function getEnable(): bool;
    public function setEnable(bool $value): bool;

    public function getDutyCycle(): int;
    public function setDutyCycle(int $value): int;

    public function getPolarity(): bool;
    public function setPolarity(bool $value): bool;

    public function channel(): int;

    /** The same calls as promises. Null pool: the adapter's own async path ('thread' or 'process' names a worker pool). */
    public function via(?string $pool = null): OffloadedPWM;
}
