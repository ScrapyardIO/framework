<?php

namespace GeneralPurposeIO\Digital;

use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use GeneralPurposeIO\Digital\Drivers\DigitalPinDriver;

class DigitalInput extends DigitalPin
{
    public function __construct(
        int $pin,
        string $consumer,
        DigitalPinDriver $driver,
        protected int $timeout_ms,
        protected bool $rising_events,
        protected bool $falling_events,
    )
    {
        parent::__construct($pin, $consumer, $driver);
    }

    public function listen(): ?DigitalEdgeEvent
    {
        return $this->driver->listen($this->timeout_ms, $this->rising_events, $this->falling_events, $this->pin);
    }

    public function flush(): void
    {
        $stop = false;
        while(!$stop) {
            $stop = is_null($this->listen());
        }
    }
}
