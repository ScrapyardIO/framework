<?php

namespace Waveforms\Carriers\GPIO\API\Native;

use Microscrap\Bindings\GPIO\Enums\GPIOV2LineFlag;
use Waveforms\Carriers\GPIO\GPIOPin;

abstract class NativeGPIOLine extends GPIOPin
{
    public array $flags = [];

    public function __construct(
        public int $line
    ) {}

    public static function line(int $line): static
    {
        return new static($line);
    }

    public function isActiveLow(): static
    {
        $this->flags['active_low'] = GPIOV2LineFlag::ACTIVE_LOW;

        return $this;
    }

    public function isNotActiveLow(): static
    {
        if (isset($this->flags['active_low'])) {
            unset($this->flags['active_low']);
        }

        return $this;
    }

    // public function openDrain(): static
    // public function notOpenDrain(): static
    // public function openSource(): static
    // public function notOpenSource(): static

    // public function hasPullUp(): static
    // public function noPullUp(): static
    // public function hasPullDown(): static
    // public function noPulldown(): static
    // public function noBias(): static

    // public function realtimeClock(): static
    // public function noRealtimeClock(): static

    // public function hwEventTimestamps(): static
    // public function noHwEventTimestamps(): static
}
