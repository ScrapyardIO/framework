<?php

namespace GeneralPurposeIO\UART;

use Closure;
use GeneralPurposeIO\Contracts\UART\UARTReceived;
use Voyager\Contracts\IOPools\Pumpable;
use Voyager\IOPools\Resources\WakeSource;
use Voyager\IOPools\Waiter\Wakes\Readable;

/** What a port registers on the loop: a readable wake on its intake stream, a collect when it fires, and the mail it posts. */
final class PortWatch extends WakeSource implements Pumpable
{
    /** @var list<UARTReceived> */
    private array $mail = [];

    public function __construct(
        private readonly Closure $streams,
        private readonly Closure $collect,
    ) {}

    public function wakes(): array
    {
        return array_map(fn (mixed $stream): Readable => new Readable($stream), ($this->streams)());
    }

    public function woke(array $fired): void
    {
        ($this->collect)();
    }

    public function pump(): array
    {
        [$mail, $this->mail] = [$this->mail, []];

        return $mail;
    }

    public function post(UARTReceived $received): void
    {
        $this->mail[] = $received;
    }
}
