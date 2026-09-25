<?php

namespace GeneralPurposeIO\SPI;

use Closure;
use Fiber;
use GeneralPurposeIO\Contracts\NutsAndBolts\BusJob;
use GeneralPurposeIO\Contracts\SPI\SPIException;
use GeneralPurposeIO\Contracts\SPI\SPITransport as TransportContract;
use Voyager\Contracts\IOPools\Promise;

abstract class SPITransport implements TransportContract
{
    private bool $closed = false;

    /** close() is waiting for this slave's running job: no new offloads. */
    private bool $closing = false;

    /** Inside this slave's select(): every call made where it runs keeps chip select asserted. */
    private bool $selected = false;

    /** The fiber the select() runs in; null for the main stack. */
    private ?Fiber $selected_in = null;

    /** This slave's own clock in Hz, or null for the connection's. Adapters set it in speed(). */
    protected ?int $hz = null;

    private ?SPIConnectionDriver $driver = null;

    private string|int|null $device = null;

    public function __construct(
        public readonly int $chip_select
    ) {}

    abstract public function handle(): mixed;

    /** Give back what this slave alone holds; the bus stays open for the other slaves. */
    abstract protected function release(): void;

    /** select() starts: chip select stays asserted from here until endSelection(). */
    abstract protected function beginSelection(): void;

    /** select() ends: chip select goes up. */
    abstract protected function endSelection(): void;

    public function chipSelect(): int
    {
        return $this->chip_select;
    }

    public function closed(): bool
    {
        return $this->closed;
    }

    /** This slave's own clock in Hz, or null while it runs at the connection's. */
    public function clock(): ?int
    {
        return $this->hz;
    }

    /** Wire-internal: the driver that handed this slave out, and the bus it rides on. */
    public function attachTo(SPIConnectionDriver $driver, string|int $device): static
    {
        $this->driver = $driver;
        $this->device = $device;

        return $this;
    }

    public function via(?string $target = null): OffloadedSPITransport
    {
        $this->ensureOffloadable();

        return new OffloadedSPITransport($this, $target);
    }

    /** Wire-internal: how a via() handle queues a job, checked again at every call so a handle outlives nothing. */
    public function offload(BusJob $job, ?string $target): Promise
    {
        $this->ensureOffloadable();

        return $this->driver->offload($this->device, $this->chip_select, $job, $target);
    }

    /**
     * Runs $body with this slave's chip select held for every call it makes, and lets it go afterwards, also when
     * $body throws. While it runs the bus belongs to this slave: another slave's call on the same stack throws
     * SPIException; a call from anywhere else (another fiber, a queued job) waits until select() returns.
     */
    public function select(Closure $body): mixed
    {
        $this->ensureOpen();

        if ($this->selected()) {
            return $body($this);
        }

        $this->driver?->hold($this->device, $this);

        try {
            return $this->whileSelected(function () use ($body): mixed {
                $this->beginSelection();
                [$this->selected, $this->selected_in] = [true, Fiber::getCurrent()];

                try {
                    return $body($this);
                } finally {
                    [$this->selected, $this->selected_in] = [false, null];
                    $this->endSelection();
                }
            });
        } finally {
            $this->driver?->letGo($this->device, $this);

            if ($this->closed) {
                $this->release();       // close() inside select() waited for chip select to go up
            }
        }
    }

    /** Queued jobs for this slave are rejected, a running one finishes, then the slave closes. Inside select(), it is released once chip select goes up. */
    public function close(): void
    {
        if ($this->closed || $this->closing) {
            return;
        }

        $this->closing = true;

        try {
            $this->driver?->abandon($this->device, $this->chip_select);
        } finally {
            [$this->closing, $this->closed] = [false, true];

            if (! $this->selected) {
                $this->release();
            }
        }
    }

    /** Inside this slave's select(), where that select() runs. */
    protected function selected(): bool
    {
        return $this->selected && $this->selected_in === Fiber::getCurrent();
    }

    /** Adapter hook: runs a whole selection, chip select down to chip select up. By default it just runs it. */
    protected function whileSelected(Closure $selection): mixed
    {
        return $selection();
    }

    /**
     * Every I/O call checks this first. Outside its own select(), a call also keeps program order: it waits for the
     * jobs queued on the bus before it, and for a select() running elsewhere.
     * @throws SPIException the slave is closed, or another slave's select() holds the bus on this very stack
     */
    protected function ensureOpen(): void
    {
        if ($this->closed) {
            throw SPIException::transportClosed($this->chip_select);
        }

        if ($this->selected()) {
            return;
        }

        $this->driver?->ensureFree($this->device, $this);        // a select() on this stack throws before anything waits
        $this->driver?->drain($this->device, $this->chip_select);
        $this->driver?->ensureFree($this->device, $this);        // one elsewhere may have taken the bus meanwhile
    }

    /**
     * @throws SPIException
     */
    private function ensureOffloadable(): void
    {
        if ($this->closed || $this->closing) {
            throw SPIException::transportClosed($this->chip_select);
        }

        if (is_null($this->driver)) {
            throw SPIException::notAttached($this->chip_select);
        }
    }
}
