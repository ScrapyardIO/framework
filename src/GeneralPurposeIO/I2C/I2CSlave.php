<?php

namespace GeneralPurposeIO\I2C;

use GeneralPurposeIO\Contracts\I2C\I2CDriver;

class I2CSlave
{
    public function __construct(
        public readonly int $address,
        protected I2CDriver $driver,
    ) {}

    public function read(int $len): array|false
    {
        return $this->driver->read($this->address, $len);
    }

    public function write(array|string $data): int
    {
        return $this->driver->write($this->address, $data);
    }

    public function writeRead(array|string $bytes_to_write, int $bytes_to_read): array|false
    {
        return $this->driver->writeRead($this->address, $bytes_to_write, $bytes_to_read);
    }

    public function bulkWrite(array|string $messages): array|false
    {
        return $this->driver->bulkWrite($this->address, $messages);
    }

    public function close(): void
    {
        $this->driver->close();
    }
}