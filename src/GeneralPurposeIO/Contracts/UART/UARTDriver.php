<?php

namespace GeneralPurposeIO\Contracts\UART;

interface UARTDriver
{
    public function path(): string;

    public function pollBytes(int $max_bytes = 4096): string;

    public function read(int $length): array|false;

    public function write(array|string $data): int;

    public function flush(): void;

    public function close(): void;
}
