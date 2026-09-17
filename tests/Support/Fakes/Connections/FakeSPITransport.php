<?php

namespace ScrapyardIO\Tests\Support\Fakes\Connections;

use GeneralPurposeIO\SPI\SPITransport;

class FakeSPITransport extends SPITransport
{
    /** @var list<array|string> */
    public array $writes = [];

    /** @var list<list<int>> */
    public array $responses = [];

    public function __construct(int $chip_select, public readonly FakeHandle $handle)
    {
        parent::__construct($chip_select);
    }

    public function handle(): FakeHandle
    {
        return $this->handle;
    }

    public function read(int $len): array|false
    {
        return array_shift($this->responses) ?? false;
    }

    public function write(array|string $data): int
    {
        $this->writes[] = $data;

        return is_array($data) ? count($data) : strlen($data);
    }

    public function transfer(array|string $data): array|false
    {
        $this->writes[] = $data;

        return array_shift($this->responses) ?? false;
    }

    public function close(): void
    {
        $this->handle->closed = true;
    }
}
