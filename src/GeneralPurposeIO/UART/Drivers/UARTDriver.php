<?php

namespace GeneralPurposeIO\UART\Drivers;

use GeneralPurposeIO\Contracts\UART\UARTAPI;

abstract class UARTDriver
{
    abstract public function close(): void;

    abstract public function flush(): void;

    abstract public function read(int $len): array|false;

    abstract public function write(array|string $data): int;
}
