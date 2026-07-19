<?php

namespace GeneralPurposeIO\Contracts\I2C;

interface I2CAPI
{

    public function read(int $len): array|false;

    public function write(array|string $data): int;

    public function writeRead(array|string $bytes_to_write, int $bytes_to_read): array|false;

    public function bulkWrite(array|string $messages): array|false;
}
