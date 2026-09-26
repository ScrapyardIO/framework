<?php

namespace GeneralPurposeIO\UART;

use Closure;
use GeneralPurposeIO\Contracts\UART\UARTReceived;
use Voyager\Contracts\IOPools\Pumpable;
use Voyager\Contracts\IOPools\StreamWatchable;

/** What a port registers on the loop: its intake stream to select on, a tick that collects, and the mail it posts. */
final class PortWatch implements StreamWatchable, Pumpable
{
    /** @var list<UARTReceived> */
    private array $mail = [];

    public function __construct(
        private readonly Closure $streams,
        private readonly Closure $collect,
    ) {}

    public function pump(): array
    {
        [$mail, $this->mail] = [$this->mail, []];

        return $mail;
    }

    public function streams(): array
    {
        return ($this->streams)();
    }

    public function tick(): void
    {
        ($this->collect)();
    }

    public function post(UARTReceived $received): void
    {
        $this->mail[] = $received;
    }
}
