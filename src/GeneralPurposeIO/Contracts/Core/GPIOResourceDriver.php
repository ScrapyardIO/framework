<?php

namespace GeneralPurposeIO\Contracts\Core;

use Closure;
use Voyager\Contracts\IOPools\IOResourceDriver;
use Voyager\IOPools\Presumption;

/** The gpio dock resource. tick() never waits; every verb is opt-in per IC. */
interface GPIOResourceDriver extends IOResourceDriver
{
    public function watch(EdgeSource $source, bool $rising = true, bool $falling = false): static;

    public function unwatch(EdgeSource $source): static;

    public function receive(ByteSource $source, int $max_bytes = 4096): static;

    public function stopReceiving(ByteSource $source): static;

    /** Run $work on the next tick; the Presumption settles with a TransferCompletion. One in flight per name. */
    public function defer(string $name, Closure $work, ?Closure $envelope = null): Presumption;

    public function inFlight(string $name): ?Presumption;

    /** Run $work every $ticks ticks until the Recurrence is stopped; each run pushes a TransferCompletion. One per name. */
    public function every(string $name, Closure $work, int $ticks = 1): Recurrence;

    public function recurring(string $name): ?Recurrence;

    /** Hand $bytes to $write one $chunk per tick; progress on the Presumption, a TransferCompletion of bytes sent at the end. One per name. */
    public function stream(string $name, Closure $write, string $bytes, int $chunk): Presumption;

    public function streaming(string $name): ?Presumption;
}
