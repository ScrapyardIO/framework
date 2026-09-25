<?php

namespace GeneralPurposeIO\I2C;

use GeneralPurposeIO\Contracts\I2C\I2CException;
use GeneralPurposeIO\Contracts\I2C\I2CTransport as TransportContract;
use GeneralPurposeIO\Contracts\NutsAndBolts\BusJob;
use Voyager\Contracts\IOPools\Promise;

abstract class I2CTransport implements TransportContract
{
    /** i2c-dev caps one message at 8192 bytes (a longer write() is cut short, a longer I2C_RDWR message refused). Every adapter holds the same cap. */
    public const int MAX_MESSAGE = 8192;

    private bool $closed = false;

    /** close() is waiting for this slave's running job: no new offloads. */
    private bool $closing = false;

    private ?I2CConnectionDriver $driver = null;

    private string|int|null $device = null;

    public function __construct(
        public readonly int $address
    ) {
        $this->validateAddress();
    }

    abstract public function handle(): mixed;

    /** Give back what this slave alone holds; the bus stays open for the other slaves. */
    abstract protected function release(): void;

    public function address(): int
    {
        return $this->address;
    }

    public function closed(): bool
    {
        return $this->closed;
    }

    /** Wire-internal: the driver that handed this slave out, and the bus it rides on. */
    public function attachTo(I2CConnectionDriver $driver, string|int $device): static
    {
        $this->driver = $driver;
        $this->device = $device;

        return $this;
    }

    public function via(?string $target = null): OffloadedI2CTransport
    {
        $this->ensureOffloadable();

        return new OffloadedI2CTransport($this, $target);
    }

    /** Wire-internal: how a via() handle queues a job, checked again at every call so a handle outlives nothing. */
    public function offload(BusJob $job, ?string $target): Promise
    {
        $this->ensureOffloadable();

        return $this->driver->offload($this->device, $this->address, $job, $target);
    }

    /** Queued jobs for this slave are rejected, a running one finishes, then the slave closes. */
    public function close(): void
    {
        if ($this->closed || $this->closing) {
            return;
        }

        $this->closing = true;

        try {
            $this->driver?->abandon($this->device, $this->address);
        } finally {
            [$this->closing, $this->closed] = [false, true];
            $this->release();
        }
    }

    /**
     * @throws I2CException
     */
    protected function ensureOpen(): void
    {
        if ($this->closed) {
            throw I2CException::transportClosed($this->address);
        }
    }

    /**
     * @throws I2CException
     */
    private function ensureOffloadable(): void
    {
        if ($this->closed || $this->closing) {
            throw I2CException::transportClosed($this->address);
        }

        if (is_null($this->driver)) {
            throw I2CException::notAttached($this->address);
        }
    }

    /**
     * @throws I2CException
     */
    protected function ensureFits(int $length): void
    {
        if ($length > self::MAX_MESSAGE) {
            throw I2CException::messageTooLong($length);
        }
    }

    /** Blocking calls keep program order: this slave's offloaded jobs run first. */
    protected function awaitTurn(): void
    {
        $this->driver?->drain($this->device, $this->address);
    }

    /**
     * @return void
     * @throws I2CException
     */
    private function validateAddress(): void
    {
        if ($this->address < 0x03 || $this->address > 0x77) {
            throw I2CException::invalidSlaveAddress($this->address);
        }
    }

    protected static function normalizeBulkMessages(array|string $messages): array
    {
        if (is_string($messages)) {
            return [$messages];
        }

        if ($messages === []) {
            return [];
        }

        $is_single_message = array_reduce(
            $messages,
            static fn (bool $carry, mixed $byte): bool => $carry && is_int($byte),
            true,
        );

        $chunks = $is_single_message ? [$messages] : $messages;

        return array_map(
            static fn (array|string $chunk): string => is_array($chunk) ? array2bytes($chunk) : $chunk,
            $chunks,
        );
    }
}
