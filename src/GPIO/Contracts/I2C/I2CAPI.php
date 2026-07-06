<?php

namespace GPIO\Contracts\I2C;

use GPIO\Contracts\Common\GeneralPurposeIO;
use GPIO\Contracts\I2C\I2CConnectionHandle as I2CConnectionHandleInterface;

interface I2CAPI
{
    public function read(int $len): array|false;
    public function write(string|array $data): int;
    public function bulkWrite(string|array $messages): array|false;
    public function writeRead(string|array $bytes_to_write, int $bytes_to_read): array|false;
}
