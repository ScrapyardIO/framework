<?php

namespace GeneralPurposeIO\Digital\Drivers;

use Microscrap\Bindings\MPSSE\MPSSEContext;
use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use GeneralPurposeIO\Contracts\Digital\SignalEdge;
use Microscrap\Bindings\GPIO\DataObjects\GPIOLineRequest;

class UsbDigitalIODriver extends DigitalIODriver
{
    protected array $line_values = [];

    public function __construct(
        protected readonly MPSSEContext $context,
    ) {}

    public function write(GPIOLineRequest|int $pin, bool $state): bool
    {
        $written = $state
            ? mpsse_pin_high($this->context, $pin)
            : mpsse_pin_low($this->context, $pin);

        if ($written !== 0) {
            return false;
        }

        return $this->read($pin) === $state;
    }

    public function read(GPIOLineRequest|int $pin): bool
    {
        $value = mpsse_pin_state($this->context, $pin, mpsse_read_pins($this->context)) == 1;

        return $this->line_values[$pin] = $value;
    }

    public function listen(int $timeout, bool $rising_events, bool $falling_events, GPIOLineRequest|int $pin): ?DigitalEdgeEvent
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

    public function pollEdges(GPIOLineRequest|int $pin, bool $rising_events, bool $falling_events, int $max_events = 16): array
    {
        if (! isset($this->line_values[$pin])) {
            $this->read($pin);

            return [];
        }

        $previous = $this->line_values[$pin];
        $event = $this->toDigitalInputEvent($previous, $this->read($pin), $rising_events, $falling_events);

        return is_null($event) ? [] : [$event];
    }

    public function close(): void
    {
        mpsse_close($this->context);
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

    public function getContext(): MPSSEContext
    {
        return $this->context;
    }
}