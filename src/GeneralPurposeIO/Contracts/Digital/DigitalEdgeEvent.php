<?php

namespace GeneralPurposeIO\Contracts\Digital;

use Voyager\Contracts\Signals\NamedSignal;

/**
 * One edge on one pin. Mailed as gpio.edge.<device>.<pin>; crosses a worker or Redis wire through toData()/fromData().
 */
class DigitalEdgeEvent implements NamedSignal
{
    /**
     *
     * @param string|int $device
     * @param int $pin
     * @param SignalEdge $edge
     * @param int $timestamp - CLOCK_MONOTONIC ns: the kernel's stamp on Linux, the sample time on MPSSE
     * @param int $seqno - Per pin: the kernel's line_seqno on Linux, the sample count on MPSSE. A gap is a dropped edge.
     */
    public function __construct(
        public readonly string|int $device,
        public readonly int $pin,
        public readonly SignalEdge $edge,
        public readonly int $timestamp,
        public readonly int $seqno,
    ) {}

    public function name(): string
    {
        return "gpio.edge.{$this->device}.{$this->pin}";
    }

    public function uuid(): string
    {
        return "{$this->name()}.{$this->timestamp}.{$this->seqno}";
    }

    public function toData(): array
    {
        return [...get_object_vars($this), 'edge' => $this->edge->value];
    }

    public static function fromData(array $data): static
    {
        $payload = [...$data, 'edge' => SignalEdge::from($data['edge'])];
        return new static(...$payload);
    }
}
