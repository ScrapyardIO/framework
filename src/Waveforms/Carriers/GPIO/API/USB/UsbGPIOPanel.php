<?php

namespace Waveforms\Carriers\GPIO\API\USB;

use Microscrap\Bindings\MPSSE\MPSSEContext;
use Waveforms\Carriers\GPIO\Events\EdgeFallingEvent;
use Waveforms\Carriers\GPIO\Events\EdgeRisingEvent;
use Waveforms\Carriers\GPIO\Events\GPIOEdgeEvent;
use Waveforms\Carriers\GPIO\GPIOPanel;

class UsbGPIOPanel extends GPIOPanel
{
    /** Sentinel value indicating no baseline sample has been taken yet. */
    private int $previousState = -1;

    public function __construct(
        public readonly MPSSEContext $chip,
        public readonly int $pin
    ) {}

    public function high(): int
    {
        return mpsse_pin_high($this->chip, $this->pin);
    }

    public function low(): int
    {
        return mpsse_pin_low($this->chip, $this->pin);
    }

    /**
     * Read the current pin state and prime the baseline used by listen().
     * Call this before a trigger pulse to ensure listen() has a valid
     * reference point for the first edge comparison.
     */
    public function read(): int
    {
        $state = mpsse_pin_state($this->chip, $this->pin, mpsse_read_pins($this->chip));
        $this->previousState = $state;

        return $state;
    }

    /**
     * Poll for a pin-state change and emulate an edge event.
     *
     * MPSSE has no hardware interrupt or edge-event API; edge detection is
     * achieved by comparing the current pin snapshot against the last sampled
     * state.  Returns null when the state is unchanged or when no baseline
     * has been established (call read() first to prime the reference).
     */
    public function listen(): ?GPIOEdgeEvent
    {
        $current = mpsse_pin_state($this->chip, $this->pin, mpsse_read_pins($this->chip));

        if ($this->previousState === -1 || $current === $this->previousState) {
            $this->previousState = $current;

            return null;
        }

        $this->previousState = $current;

        return $current === 1
            ? new EdgeRisingEvent(hrtime(true))
            : new EdgeFallingEvent(hrtime(true));
    }
}
