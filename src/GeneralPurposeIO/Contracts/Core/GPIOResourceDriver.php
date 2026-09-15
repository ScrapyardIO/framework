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
}
