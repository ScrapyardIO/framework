<?php

namespace ScrapyardIO\Tests\Support\Fakes;

use GeneralPurposeIO\UART\Drivers\UARTDriver;

class FakeUARTDriver extends UARTDriver
{
    public array $reads = [];

    public array $writes = [];

    public int $flushes = 0;

    public int $closes = 0;

    public string $buffered = '';

    public function path(): string
    {
        return 'fake:0';
    }

    public function pollBytes(int $max_bytes = 4096): string
    {
        $out = substr($this->buffered, 0, $max_bytes);
        $this->buffered = substr($this->buffered, strlen($out));

        return $out;
    }

    public function read(int $length): array|false
    {
        $this->reads[] = $length;

        return [0x41, 0x42];
    }

    public function write(array|string $data): int
    {
        $this->writes[] = $data;

        return is_array($data) ? count($data) : strlen($data);
    }

    public function flush(): void
    {
        $this->flushes++;
    }

    public function close(): void
    {
        $this->closes++;
    }
}
