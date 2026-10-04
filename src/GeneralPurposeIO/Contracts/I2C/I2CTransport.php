<?php

namespace GeneralPurposeIO\Contracts\I2C;

use GeneralPurposeIO\Contracts\NutsAndBolts\GPIOTransport;

interface I2CTransport extends GPIOTransport
{
    public function probe(): bool;
    public function read(int $len): array|false;
    public function write(array|string $data): int;
    public function writeRead(array|string $bytes_to_write, int $bytes_to_read): array|false;
    public function bulkWrite(array|string $messages): array|false;

    /** The same calls as promises. Null pool: the adapter's own async path ('thread' or 'process' names a worker pool). */
    public function via(?string $pool = null): OffloadedI2C;
}
