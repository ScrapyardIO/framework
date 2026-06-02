<?php

namespace Waveforms\Carriers\GPIO\API\Native;

use Microscrap\Bindings\GPIO\DataObjects\GPIOEdgeEventBuffer;
use Microscrap\Bindings\GPIO\DataObjects\GPIOLineRequest;
use Microscrap\Bindings\GPIO\Enums\EdgeEventType;
use Microscrap\Bindings\GPIO\Enums\LineValue;
use Waveforms\Carriers\GPIO\Events\EdgeFallingEvent;
use Waveforms\Carriers\GPIO\Events\EdgeRisingEvent;
use Waveforms\Carriers\GPIO\Events\GPIOEdgeEvent;
use Waveforms\Carriers\GPIO\GPIOPanel;

class NativeGPIOPanel extends GPIOPanel
{
    public function __construct(
        public readonly GPIOLineRequest $line_fd,
        public readonly int $line
    ) {}

    public function high(): int
    {
        return gpiod_line_request_set_value($this->line_fd, $this->line, LineValue::Active);
    }

    public function low(): int
    {
        return gpiod_line_request_set_value($this->line_fd, $this->line, LineValue::Inactive);
    }

    public function read(): int
    {
        return gpiod_line_request_get_value($this->line_fd, $this->line)->value;
    }

    public function listen(): ?GPIOEdgeEvent
    {
        $buffer = gpiod_edge_event_buffer_new(1);

        if (! $buffer instanceof GPIOEdgeEventBuffer) {
            return null;
        }

        $count = gpiod_line_request_read_edge_events($this->line_fd, $buffer, 1);

        if ($count < 1) {
            return null;
        }

        $event = gpiod_edge_event_buffer_get_event($buffer, 0);

        return match ($event->event_type) {
            EdgeEventType::RISING_EDGE => new EdgeRisingEvent($event->timestamp_ns),
            EdgeEventType::FALLING_EDGE => new EdgeFallingEvent($event->timestamp_ns),
        };
    }
}
