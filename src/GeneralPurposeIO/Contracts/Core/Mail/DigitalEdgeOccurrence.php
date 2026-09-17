<?php

namespace GeneralPurposeIO\Contracts\Core\Mail;

use GeneralPurposeIO\Contracts\Digital\SignalEdge;
use Voyager\Contracts\IOPools\Occurrence;

/** A watched line changed. Named gpio.edge.<device>.<offset>, or gpio.edge.<offset> for an unbound pin. Timestamp is the kernel clock in ns. */
final class DigitalEdgeOccurrence implements Occurrence
{
    public readonly string $name;

    public function __construct(
        public readonly string|int|null $device,
        public readonly int $offset,
        public readonly SignalEdge $edge,
        public readonly int $timestamp_ns,
    ) {
        $this->name = is_null($device) ? "gpio.edge.{$offset}" : "gpio.edge.{$device}.{$offset}";
    }
}
