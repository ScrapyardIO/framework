<?php

namespace ScrapyardIO\Tests\Support\Fakes;

use GeneralPurposeIO\I2C\Drivers\I2CDriver;

class FakeI2CDriver extends I2CDriver
{
    public function close(): void {}

    public function probe(int $address): bool
    {
        return in_array($address, [0x3c, 0x38], true);
    }

    public function read(int $address, int $len): array|false
    {
        return false;
    }

    public function write(int $address, array|string $data): int
    {
        return 0;
    }

    public function writeRead(int $address, array|string $bytes_to_write, int $bytes_to_read): array|false
    {
        return false;
    }

    public function bulkWrite(int $address, array|string $messages): array|false
    {
        return false;
    }
}
