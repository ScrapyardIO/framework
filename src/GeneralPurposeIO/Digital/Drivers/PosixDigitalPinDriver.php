<?php

namespace GeneralPurposeIO\Digital\Drivers;

use Microscrap\Bindings\GPIO\Enums\LineValue;
use Microscrap\Bindings\GPIO\Enums\EdgeEventType;
use GeneralPurposeIO\Contracts\Digital\SignalEdge;
use Microscrap\Bindings\GPIO\DataObjects\GPIOEdgeEvent;
use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use Microscrap\Bindings\GPIO\DataObjects\GPIOLineRequest;

class PosixDigitalPinDriver extends DigitalPinDriver
{
    public function __construct(
        public GPIOLineRequest $handle
    ) {}

    public function write(int $pin, bool $state): bool
    {
        $value = $state ? LineValue::ACTIVE : LineValue::INACTIVE;

        gpiod_line_request_set_value($this->handle, $pin, $value);

        return $this->read($pin);
    }

    public function read(int $pin): bool
    {
        return gpiod_line_request_get_value($this->handle, $pin)->value == 1;
    }

    public function close(): void
    {
        posix_close($this->handle->fd);
    }

    public function listen(int $timeout, bool $rising_events, bool $falling_events, int $pin): ?DigitalEdgeEvent
    {
        $ready = $timeout > -1
            ? gpiod_line_request_wait_edge_events($this->handle, $timeout * 1_000_000)
            : gpiod_line_request_wait_edge_events($this->handle, -1);

        if ($ready !== 1) {
            return null;
        }

        $buffer = gpiod_edge_event_buffer_new(1);
        if (is_null($buffer) || gpiod_line_request_read_edge_events($this->handle, $buffer, 1) < 1) {
            return null;
        }

        return $this->toDigitalInputEvent(
            gpiod_edge_event_buffer_get_event($buffer, 0),
            $pin,
            $rising_events,
            $falling_events,
        );
    }

    protected function toDigitalInputEvent(?GPIOEdgeEvent $edge_event, int $pin, bool $rising_events, bool $falling_events): ?DigitalEdgeEvent
    {
        if (is_null($edge_event) || $edge_event->line_offset !== $pin) {
            return null;
        }

        $edge = match ($edge_event->event_type) {
            EdgeEventType::RISING_EDGE => $rising_events ? SignalEdge::RISING : null,
            EdgeEventType::FALLING_EDGE => $falling_events ? SignalEdge::FALLING : null,
            default => null
        };

        return is_null($edge) ? null : new DigitalEdgeEvent($edge, $edge_event->timestamp_ns);
    }
}
