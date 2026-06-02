<?php

namespace Waveforms\Carriers\GPIO\API\USB;

use Waveforms\Carriers\GPIO\GPIOPin;

abstract class UsbGPIOPin extends GPIOPin
{
    public function __construct(
        public int $pin
    ) {}

    public static function pin(int $line): static
    {
        return new static($line);
    }
}
