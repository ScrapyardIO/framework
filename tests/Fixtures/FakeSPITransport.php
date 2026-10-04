<?php

namespace ScrapyardIO\Tests\Fixtures;

use GeneralPurposeIO\SPI\SPITransport;

/** Logs every call as "<bus>:<chip select>:<event>"; reads answer zeros, a transfer echoes like MOSI tied to MISO. */
final class FakeSPITransport extends SPITransport
{
    /** @var list<string> every event any fake slave saw, oldest first */
    public static array $log = [];

    public bool $released = false;

    public function __construct(
        int $chip_select,
        public readonly FakeSPIHandle $bus,
    ) {
        parent::__construct($chip_select);
    }

    public function handle(): FakeSPIHandle
    {
        return $this->bus;
    }

    public function read(int $len): array|false
    {
        $this->ensureOpen();
        $this->record("read:{$len}");

        return array_fill(0, $len, 0);
    }

    public function write(array|string $data): int
    {
        $bytes = is_array($data) ? array2bytes($data) : $data;

        $this->ensureOpen();
        $this->record('write:'.bin2hex($bytes));

        return strlen($bytes);
    }

    public function transfer(array|string $data): array|false
    {
        $bytes = is_array($data) ? array2bytes($data) : $data;

        $this->ensureOpen();
        $this->record('transfer:'.bin2hex($bytes));

        return bytes2array($bytes);
    }

    public function writeRead(array|string $bytes_to_write, int $bytes_to_read): array|false
    {
        $bytes = is_array($bytes_to_write) ? array2bytes($bytes_to_write) : $bytes_to_write;

        $this->ensureOpen();
        $this->record('writeRead:'.bin2hex($bytes).":{$bytes_to_read}");

        return array_fill(0, $bytes_to_read, 0);
    }

    public function speed(int $hz): static
    {
        $this->ensureOpen();
        $this->hz = $hz;
        $this->record("speed:{$hz}");

        return $this;
    }

    protected function beginSelection(): void
    {
        $this->record('select');
    }

    protected function endSelection(): void
    {
        $this->record('deselect');
    }

    protected function release(): void
    {
        $this->released = true;
        $this->record('release');
    }

    private function record(string $event): void
    {
        self::$log[] = "{$this->bus->device}:{$this->chip_select}:{$event}";
    }
}
