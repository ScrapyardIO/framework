<?php

namespace ScrapyardIO\Tests\Fixtures;

use GeneralPurposeIO\I2C\I2CTransport;

/** Records every message it is asked to send; answers reads with zeros. */
final class FakeI2CTransport extends I2CTransport
{
    /** @var list<string> every message any fake slave sent, "<bus>:<address>:<hex>", oldest first */
    public static array $log = [];

    /** @var list<string> */
    public array $writes = [];

    public bool $released = false;

    public function __construct(
        int $address,
        public readonly FakeI2CHandle $bus,
    ) {
        parent::__construct($address);
    }

    public function handle(): FakeI2CHandle
    {
        return $this->bus;
    }

    public function probe(): bool
    {
        $this->ensureOpen();
        $this->awaitTurn();

        return ! $this->bus->closed;
    }

    public function read(int $len): array|false
    {
        $this->ensureOpen();
        $this->ensureFits($len);
        $this->awaitTurn();

        return array_fill(0, $len, 0);
    }

    public function write(array|string $data): int
    {
        $bytes = is_array($data) ? array2bytes($data) : $data;

        $this->ensureOpen();
        $this->ensureFits(strlen($bytes));
        $this->awaitTurn();

        $this->record($bytes);

        return strlen($bytes);
    }

    public function writeRead(array|string $bytes_to_write, int $bytes_to_read): array|false
    {
        $bytes = is_array($bytes_to_write) ? array2bytes($bytes_to_write) : $bytes_to_write;

        $this->ensureOpen();
        $this->ensureFits(strlen($bytes));
        $this->ensureFits($bytes_to_read);
        $this->awaitTurn();

        $this->record($bytes);

        return array_fill(0, $bytes_to_read, 0);
    }

    public function bulkWrite(array|string $messages): array|false
    {
        $this->ensureOpen();

        $chunks = static::normalizeBulkMessages($messages);

        foreach ($chunks as $chunk) {
            $this->ensureFits(strlen($chunk));
        }

        $this->awaitTurn();

        foreach ($chunks as $chunk) {
            $this->record($chunk);
        }

        return array_map('strlen', $chunks);
    }

    protected function release(): void
    {
        $this->released = true;
    }

    private function record(string $bytes): void
    {
        $this->writes[] = $bytes;
        self::$log[] = "{$this->bus->device}:{$this->address}:".bin2hex($bytes);
    }
}
