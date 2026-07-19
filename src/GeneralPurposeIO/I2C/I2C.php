<?php

namespace GeneralPurposeIO\I2C;

use GeneralPurposeIO\Contracts\I2C\I2CAPI;
use GeneralPurposeIO\I2C\Drivers\I2CDriver;

class I2C implements I2CAPI
{
    public function __construct(
        protected I2CDriver $driver,
        protected int $slave_address,
    ) {}

    public function slaveAddress(): int
    {
        return $this->slave_address;
    }

    public function read(int $len): array|false
    {
        return $this->driver->read($len);
    }

    public function write(array|string $data): int
    {
        return $this->driver->write($data);
    }

    public function writeRead(array|string $bytes_to_write, int $bytes_to_read): array|false
    {
        return $this->driver->writeRead($bytes_to_write, $bytes_to_read);
    }

    public function bulkWrite(array|string $messages): array|false
    {
        return $this->driver->bulkWrite($messages);
    }

    public function close(): void
    {
        $this->driver->close();
    }
}
