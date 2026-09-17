<?php

namespace GeneralPurposeIO\Contracts\UART;

use GeneralPurposeIO\Contracts\NutsAndBolts\GPIOTransport;

interface UARTTransport extends GPIOTransport
{
    public function flush(): void;
    public function path(): string;
    public function read(int $length): array|false;
    public function write(array|string $data): int;
    public function pollBytes(int $max_bytes = 4096): string;
}