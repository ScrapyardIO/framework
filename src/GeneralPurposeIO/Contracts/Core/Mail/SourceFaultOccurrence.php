<?php

namespace GeneralPurposeIO\Contracts\Core\Mail;

use Throwable;
use Voyager\Contracts\IOPools\Occurrence;

/**
 * Something polled or run on the tick threw. Named gpio.fault.<source>:
 * edge.<device>.<offset>, uart.<path>, every.<name>. The source stays
 * registered; dropping it is the IC's call.
 */
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
