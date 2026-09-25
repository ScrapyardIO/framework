<?php

namespace GeneralPurposeIO\Contracts\SPI;

use Closure;
use GeneralPurposeIO\Contracts\NutsAndBolts\GPIOTransport;

interface SPITransport extends GPIOTransport
{
    /** Clocks $len bytes in (sending zeros), chip select asserted for the call. */
    public function read(int $len): array|false;

    /** Clocks $data out: how many bytes, or -1. */
    public function write(array|string $data): int;

    /** Full duplex: $data out, as many bytes in. */
    public function transfer(array|string $data): array|false;

    /** $bytes_to_write out, then $bytes_to_read in (sending zeros), chip select held across both. */
    public function writeRead(array|string $bytes_to_write, int $bytes_to_read): array|false;

    /** Runs $body with this slave's chip select held for every call it makes; released after, also when $body throws. */
    public function select(Closure $body): mixed;

    /** This slave's clock in Hz from now on. Until called, the connection's. */
    public function speed(int $hz): static;

    public function chipSelect(): int;

    /** The same calls as promises. Null target: the adapter's own async path. */
    public function via(?string $target = null): OffloadedSPI;
}
