<?php

namespace ScrapyardIO\Tests\Support\Fakes\Connections;

use GeneralPurposeIO\I2C\I2CTransport;

class FakeI2CTransport extends I2CTransport
{
    /** @var list<array|string> */
    public array $writes = [];

    /** @var list<array{0: array|string, 1: int}> */
    public array $write_reads = [];

    /** @var list<int> */
    public array $reads = [];

    /** @var list<list<int>> */
    public array $responses = [];

    public function __construct(int $address, public readonly FakeHandle $handle)
    {
        parent::__construct($address);
    }

    public function handle(): FakeHandle
    {
        return $this->handle;
    }

    public function probe(): bool
    {
        return ! $this->handle->closed;
    }

    public function read(int $len): array|false
    {
        $this->reads[] = $len;

        return array_shift($this->responses) ?? false;
    }

    public function write(array|string $data): int
    {
        $this->writes[] = $data;

        return is_array($data) ? count($data) : strlen($data);
    }

    public function writeRead(array|string $bytes_to_write, int $bytes_to_read): array|false
    {
        $this->write_reads[] = [$bytes_to_write, $bytes_to_read];

        return array_shift($this->responses) ?? false;
    }

    /** Runs the base normaliser, which is the behaviour under test. */
    public function bulkWrite(array|string $messages): array|false
    {
        $chunks = static::normalizeBulkMessages($messages);
        $this->writes = array_merge($this->writes, $chunks);

        return array_map('strlen', $chunks);
    }

    public function close(): void
    {
        $this->handle->closed = true;
    }
}
