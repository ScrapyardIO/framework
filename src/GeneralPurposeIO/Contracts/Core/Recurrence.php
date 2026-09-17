<?php

namespace GeneralPurposeIO\Contracts\Core;

use Closure;
use GeneralPurposeIO\Contracts\Core\Mail\TransferCompletion;
use Throwable;

/**
 * The handle every() returns. A recurrence never settles; it runs until
 * stop(). onEach hears every completed run, onFail every run that threw.
 */
final class Recurrence
{
    protected ?Closure $on_each = null;

    protected ?Closure $on_fail = null;

    protected bool $stopped = false;

    protected int $runs = 0;

    public function __construct(
        public readonly string $name,
        public readonly int $ticks,
    ) {}

    public function onEach(callable $hook): static
    {
        $this->on_each = $hook(...);

        return $this;
    }

    public function onFail(callable $hook): static
    {
        $this->on_fail = $hook(...);

        return $this;
    }

    public function stop(): void
    {
        $this->stopped = true;
    }

    public function stopped(): bool
    {
        return $this->stopped;
    }

    public function runs(): int
    {
        return $this->runs;
    }

    /** Wire-internal: a run returned. */
    public function ran(TransferCompletion $completion): void
    {
        $this->runs++;

        if (! is_null($this->on_each)) {
            ($this->on_each)($completion);
        }
    }

    /** Wire-internal: a run threw. */
    public function failed(Throwable $error): void
    {
        $this->runs++;

        if (! is_null($this->on_fail)) {
            ($this->on_fail)($error);
        }
    }
}
