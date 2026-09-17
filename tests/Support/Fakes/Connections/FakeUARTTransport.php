<?php

namespace ScrapyardIO\Tests\Support\Fakes\Connections;

use GeneralPurposeIO\UART\UARTTransport;

class FakeUARTTransport extends UARTTransport
{
    /** @var list<string> bytes as the base normaliser produced them */
    public array $writes = [];

    public int $flushes = 0;

    public string $buffered = '';

    public function __construct(public readonly string $path, public readonly FakeHandle $handle) {}

    public function handle(): FakeHandle
    {
        return $this->handle;
    }

    public function flush(): void
    {
        $this->flushes++;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function read(int $length): array|false
    {
        $out = substr($this->buffered, 0, $length);
        $this->buffered = substr($this->buffered, strlen($out));

        return $out === '' ? false : array_values(unpack('C*', $out));
    }

    /** Runs the base normaliser, which is the behaviour under test. */
    public function write(array|string $data): int
    {
        $bytes = static::normalizeData($data);
        $this->writes[] = $bytes;

        return strlen($bytes);
    }

    public function pollBytes(int $max_bytes = 4096): string
    {
        $out = substr($this->buffered, 0, $max_bytes);
        $this->buffered = substr($this->buffered, strlen($out));

        return $out;
    }

    public function close(): void
    {
        $this->handle->closed = true;
    }
}
