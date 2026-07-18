<?php

namespace GeneralPurposeIO\Digital\Drivers;

use Microscrap\Bindings\MPSSE\MPSSEContext;
use GeneralPurposeIO\Contracts\Digital\SignalEdge;
use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;

class UsbDigitalPinDriver extends DigitalPinDriver
{
    protected array $line_values = [];

    public function __construct(
        public readonly MPSSEContext $handle
    ) {}

    public function write(int $pin, bool $state): bool
    {
         $state
            ? mpsse_pin_high($this->handle, $pin)
            : mpsse_pin_low($this->handle, $pin);

        return $this->read($pin);
    }

    public function read(int $pin): bool
    {
        $value = mpsse_pin_state($this->handle, $pin, mpsse_read_pins($this->handle)) == 1;

        return $this->line_values[$pin] = $value;
    }

    public function close(): void
    {
        mpsse_close($this->handle);
    }

    public function listen(int $timeout, bool $rising_events, bool $falling_events, int $pin): ?DigitalEdgeEvent
    {
        if ($timeout < 0) {
            return null;
        }

        $previous = $this->line_values[$pin] ?? $this->read($pin);

        if ($timeout === 0) {
            return $this->toDigitalInputEvent($previous, $this->read($pin), $rising_events, $falling_events);
        }

        $deadline_ns = hrtime(true) + ($timeout * 1_000_000);
        do {
            $current = $this->read($pin);
            $event = $this->toDigitalInputEvent($previous, $current, $rising_events, $falling_events);
            if (! is_null($event)) {
                return $event;
            }

            $previous = $current;
            usleep(1_000);
        } while (hrtime(true) < $deadline_ns);

        return null;
    }

    protected function toDigitalInputEvent(bool $previous, bool $current, bool $rising_events, bool $falling_events): ?DigitalEdgeEvent
    {
        if ($previous === $current) {
            return null;
        }

        $edge = $current
            ? ($rising_events ? SignalEdge::RISING : null)
            : ($falling_events ? SignalEdge::FALLING : null);

        return is_null($edge) ? null : new DigitalEdgeEvent($edge, hrtime(true));
    }
}
