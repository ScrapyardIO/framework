<?php

namespace GeneralPurposeIO\Digital;

use GeneralPurposeIO\Contracts\Digital\DigitalIOException;
use GeneralPurposeIO\Contracts\Digital\DigitalOutTransport as TransportContract;
use GeneralPurposeIO\NutsAndBolts\CarrierTransport;

abstract class DigitalOutputTransport extends CarrierTransport implements TransportContract
{
    private bool $closed = false;

    public function __construct(
        public readonly int $pin,
    ) {}

    /**
     * Give back what this pin alone holds; the connection stays open.
     */
    abstract protected function release(): void;

    public function low(): void
    {
        $this->write(false);
    }

    public function high(): void
    {
        $this->write(true);
    }

    public function closed(): bool
    {
        return $this->closed;
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;
        $this->release();
    }

    protected function ensureOpen(): void
    {
        if ($this->closed) {
            throw DigitalIOException::pinClosed($this->pin);
        }
    }
}
