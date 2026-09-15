<?php

namespace GeneralPurposeIO\Contracts\Core\Mail;

use Voyager\Contracts\IOPools\Occurrence;

/** Bytes arrived on a received port. Named gpio.uart.<path>. Framing is the IC's job. */
final class UARTBytesOccurrence implements Occurrence
{
    public readonly string $name;

    public function __construct(
        public readonly string $path,
        public readonly string $bytes,
    ) {
        $this->name = "gpio.uart.{$path}";
    }
}
