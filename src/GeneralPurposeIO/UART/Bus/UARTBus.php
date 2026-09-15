<?php

namespace GeneralPurposeIO\UART\Bus;

use GeneralPurposeIO\Contracts\Core\ByteSource;
use GeneralPurposeIO\Contracts\UART\UARTDriver;

abstract class UARTBus implements ByteSource
{
    public function __construct(
        protected UARTDriver $driver,
    ) {}

    public function path(): string
    {
        return $this->driver->path();
    }

    public function pollBytes(int $max_bytes = 4096): string
    {
        return $this->driver->pollBytes($max_bytes);
    }

    public function read(int $length): array|false
    {
        return $this->driver->read($length);
    }

    public function write(array|string $data): int
    {
        return $this->driver->write($data);
    }

    public function flush(): void
    {
        $this->driver->flush();
    }

    public function close(): void
    {
        $this->driver->close();
    }

    public function driver(): UARTDriver
    {
        return $this->driver;
    }
}
