<?php

namespace GeneralPurposeIO\Digital\Drivers;

use GeneralPurposeIO\Contracts\Digital\SignalEdge;
use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use Microscrap\Bindings\GPIO\DataObjects\GPIOChip;
use Microscrap\Bindings\GPIO\DataObjects\GPIOEdgeEvent;
use Microscrap\Bindings\GPIO\DataObjects\GPIOLineRequest;
use Microscrap\Bindings\GPIO\DataObjects\GPIORequestConfig;
use Microscrap\Bindings\GPIO\Enums\EdgeEventType;
use Microscrap\Bindings\GPIO\Enums\LineValue;

class PosixDigitalIODriver extends DigitalIODriver
{
    public function __construct(
        public readonly GPIORequestConfig $req_config,
        public readonly GPIOChip $chip,
    ) {}

    public function write(GPIOLineRequest|int $pin, bool $state): bool
    {
        $value = $state ? LineValue::ACTIVE : LineValue::INACTIVE;

        gpiod_line_request_set_value($pin, $pin->offsets[0], $value);

        return $this->read($pin);
    }

    public function read(GPIOLineRequest|int $pin): bool
    {
        return gpiod_line_request_get_value($pin, $pin->offsets[0])->value == 1;
    }

    public function listen(int $timeout, bool $rising_events, bool $falling_events, GPIOLineRequest|int $pin): ?DigitalEdgeEvent
    {
        $ready = $timeout > -1
            ? gpiod_line_request_wait_edge_events($pin, $timeout * 1_000_000)
            : gpiod_line_request_wait_edge_events($pin, -1);

        if ($ready !== 1) {
            return null;
        }

        $buffer = gpiod_edge_event_buffer_new(1);
        if (is_null($buffer) || gpiod_line_request_read_edge_events($pin, $buffer, 1) < 1) {
            return null;
        }

        return $this->toDigitalInputEvent(
            gpiod_edge_event_buffer_get_event($buffer, 0),
            $pin->offsets[0],
            $rising_events,
            $falling_events,
        );
    }

    public function pollEdges(GPIOLineRequest|int $pin, bool $rising_events, bool $falling_events, int $max_events = 16): array
    {
        if (! $pin instanceof GPIOLineRequest || gpiod_line_request_wait_edge_events($pin, 0) !== 1) {
            return [];
        }

        $buffer = gpiod_edge_event_buffer_new($max_events);

        if (is_null($buffer)) {
            return [];
        }

        $count = gpiod_line_request_read_edge_events($pin, $buffer, $max_events);
        $events = [];

        for ($i = 0; $i < $count; $i++) {
            $event = $this->toDigitalInputEvent(gpiod_edge_event_buffer_get_event($buffer, $i), $pin->offsets[0], $rising_events, $falling_events);

            if (! is_null($event)) {
                $events[] = $event;
            }
        }

        return $events;
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

    public function close(): void
    {
        gpiod_chip_close($this->chip);
    }
}