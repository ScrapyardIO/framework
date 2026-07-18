<?php

namespace GeneralPurposeIO\UART;

use GeneralPurposeIO\UART\Drivers\UARTDriver;

class UART
{
    public function __construct(
        protected UARTDriver $driver,
    ) {}

    public function read(int $len): array|false
    {
        return $this->driver->read($len);
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
}
