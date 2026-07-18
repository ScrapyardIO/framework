<?php

namespace GeneralPurposeIO\SPI;

use GeneralPurposeIO\SPI\Drivers\SPIDriver;

class SPI
{
    public function __construct(
        protected SPIDriver $driver,
    ) {}

    public function read(int $len): array|false
    {
        return $this->driver->read($len);
    }

    public function write(array|string $data): int
    {
        return $this->driver->write($data);
    }

    public function transfer(array|string $data): array|false
    {
        return $this->driver->transfer($data);
    }

    public function close(): void
    {
        $this->driver->close();
    }
}
