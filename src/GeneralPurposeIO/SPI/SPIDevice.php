<?php

namespace GeneralPurposeIO\SPI;

use GeneralPurposeIO\SPI\Drivers\SPIDriver;

class SPIDevice
{
    public function __construct(
        protected $chip,
        protected SPIDriver $driver
    ) {}

    public function read(int $len): array|false
    {
        return $this->driver->read($this->chip, $len);
    }

    public function write(array|string $data): int
    {
        return $this->driver->write($this->chip, $data);
    }

    public function transfer(array|string $data): array|false
    {
        return $this->driver->transfer($this->chip, $data);
    }
    public function close(): void
    {
        $this->driver->close();
    }
}