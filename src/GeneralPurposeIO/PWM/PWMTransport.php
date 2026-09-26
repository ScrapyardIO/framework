<?php

namespace GeneralPurposeIO\PWM;

use GeneralPurposeIO\Contracts\NutsAndBolts\BusJob;
use GeneralPurposeIO\Contracts\PWM\PWMException;
use GeneralPurposeIO\Contracts\PWM\PWMTransport as TransportContract;
use Voyager\Contracts\IOPools\Promise;

abstract class PWMTransport implements TransportContract
{
    private bool $closed = false;

    /** close() is waiting for this channel's running job: no new offloads. */
    private bool $closing = false;

    private ?PWMConnectionDriver $driver = null;

    private string|int|null $device = null;

    public function __construct(
        public readonly int $channel
    ) {}

    abstract public function handle(): mixed;

    /** Give back what this channel alone holds; the chip stays open for its other channels. */
    abstract protected function release(): void;

    public function channel(): int
    {
        return $this->channel;
    }

    public function closed(): bool
    {
        return $this->closed;
    }

    /** Wire-internal: the driver that handed this channel out, and the chip it sits on. */
    public function attachTo(PWMConnectionDriver $driver, string|int $device): static
    {
        $this->driver = $driver;
        $this->device = $device;

        return $this;
    }

    public function via(?string $target = null): OffloadedPWMTransport
    {
        $this->ensureOffloadable();

        return new OffloadedPWMTransport($this, $target);
    }

    /** Wire-internal: how a via() handle queues a job, checked again at every call so a handle outlives nothing. */
    public function offload(BusJob $job, ?string $target): Promise
    {
        $this->ensureOffloadable();

        return $this->driver->offload($this->device, $this->channel, $job, $target);
    }

    /** Queued jobs for this channel are rejected, a running one finishes, then the channel closes. */
    public function close(): void
    {
        if ($this->closed || $this->closing) {
            return;
        }

        $this->closing = true;

        try {
            $this->driver?->abandon($this->device, $this->channel);
        } finally {
            [$this->closing, $this->closed] = [false, true];
            $this->release();
        }
    }

    /**
     * @throws PWMException
     */
    protected function ensureOpen(): void
    {
        if ($this->closed) {
            throw PWMException::transportClosed($this->channel);
        }
    }

    /** Blocking calls keep program order: this channel's offloaded jobs run first. */
    protected function awaitTurn(): void
    {
        $this->driver?->drain($this->device, $this->channel);
    }

    /**
     * @throws PWMException
     */
    private function ensureOffloadable(): void
    {
        if ($this->closed || $this->closing) {
            throw PWMException::transportClosed($this->channel);
        }

        if (is_null($this->driver)) {
            throw PWMException::notAttached($this->channel);
        }
    }
}
