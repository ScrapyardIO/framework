<?php

namespace GeneralPurposeIO\Digital;

use GeneralPurposeIO\Contracts\Digital\DigitalOutTransport as TransportContract;

abstract class DigitalOutputTransport implements TransportContract
{
    public function __construct(
        public readonly int $pin,
    ) {}

    public function low(): void
    {
        $this->write(false);
    }

    public function high(): void
    {
        $this->write(true);
    }
}
