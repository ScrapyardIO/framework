<?php

namespace GeneralPurposeIO\PWM;

use GeneralPurposeIO\Contracts\PWM\PWMTransport as TransportContract;

abstract class PWMTransport implements TransportContract
{
    public function __construct(
        public readonly int $channel
    ) {}

    abstract public function handle(): mixed;

    public function channel(): int
    {
        return $this->channel;
    }
}
