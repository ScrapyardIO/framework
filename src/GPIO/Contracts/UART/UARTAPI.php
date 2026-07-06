<?php

namespace GPIO\Contracts\UART;

interface UARTAPI
{
    public function read(int $len): array|false;
    public function write(string|array $data): int;
    public function flush(): void;
}
