<?php

namespace GeneralPurposeIO\Contracts\UART;

interface UARTAPI
{
    public function flush(): void;

    public function read(int $len): array|false;

    public function write(array|string $data): int;
}
