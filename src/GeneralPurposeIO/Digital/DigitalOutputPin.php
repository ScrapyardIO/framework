<?php

namespace GeneralPurposeIO\Digital;

use GeneralPurposeIO\Contracts\Digital\DigitalIODriver;
use Microscrap\Bindings\GPIO\DataObjects\GPIOLineRequest;

class DigitalOutputPin
{
    public function __construct(
        public GPIOLineRequest|int $pin,
        protected DigitalIODriver $driver,
    ) {}

    public function read(): bool
    {
        return $this->driver->read($this->pin);
    }

    public function write(bool $value): bool
    {
        return $this->driver->write($this->pin, $value);
    }

    public function high(): bool
    {
        $this->write(true);
        return $this->read();
    }

    public function low(): bool
    {
        $this->write(false);
        return $this->read();
    }

    public function isHigh(): bool
    {
        return $this->read();
    }

    public function isLow(): bool
    {
        return !$this->read();
    }

    public function close(): void
    {
        if ($this->pin instanceof GPIOLineRequest) {
            posix_close($this->pin->fd);
        }

        $this->driver->close();
    }
}