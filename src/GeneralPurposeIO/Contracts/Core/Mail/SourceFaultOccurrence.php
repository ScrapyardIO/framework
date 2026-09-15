<?php

namespace GeneralPurposeIO\Contracts\Core\Mail;

use Throwable;
use Voyager\Contracts\IOPools\Occurrence;

/** A watched pin or received port threw while being polled. Named gpio.fault.<source>, e.g. gpio.fault.edge.17 or gpio.fault.uart./dev/ttyAMA0. The source stays registered; unwatching is the IC's call. */
final class SourceFaultOccurrence implements Occurrence
{
    public readonly string $name;

    public function __construct(
        public readonly string $source,
        public readonly Throwable $error,
    ) {
        $this->name = "gpio.fault.{$source}";
    }
}
