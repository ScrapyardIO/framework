<?php

namespace GeneralPurposeIO\Digital;

use GeneralPurposeIO\Contracts\Core\EdgeSource;
use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use GeneralPurposeIO\Contracts\Digital\DigitalIODriver;
use Microscrap\Bindings\GPIO\DataObjects\GPIOLineRequest;

class DigitalInputPin implements EdgeSource
{
    public function __construct(
        public GPIOLineRequest|int $pin,
        protected DigitalIODriver $driver,
    ) {}

    public function read(): bool
    {
        return $this->driver->read($this->pin);
    }

    public function isHigh(): bool
    {
        return $this->read();
    }

    public function isLow(): bool
    {
        return !$this->read();
    }

    public function listen(bool $rising_events = false, bool $falling_events = false, int $timeout_ms = 1000): ?DigitalEdgeEvent
    {
        return $this->driver->listen($timeout_ms, $rising_events, $falling_events, $this->pin);
    }

    public function offset(): int
    {
        return $this->pin instanceof GPIOLineRequest ? $this->pin->offsets[0] : $this->pin;
    }

    public function pollEdges(bool $rising = true, bool $falling = false): array
    {
        return $this->driver->pollEdges($this->pin, $rising, $falling);
    }

    public function close(): void
    {
        if ($this->pin instanceof GPIOLineRequest) {
            posix_close($this->pin->fd);
        }

        $this->driver->close();
    }
}