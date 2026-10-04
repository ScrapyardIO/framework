<?php

namespace GeneralPurposeIO\Digital;

use Closure;
use Voyager\Contracts\IOPools\Pumpable;
use Voyager\IOPools\Resources\WakeSource;
use Voyager\IOPools\Waiter\Wakes\Readable;
use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;

/** What a watched pin registers on the loop: a readable wake per edge stream, a collect when one fires, and the edges it mails. */
class EdgeWatch extends WakeSource implements Pumpable
{
    /** @var list<DigitalEdgeEvent> */
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

    public function post(DigitalEdgeEvent $edge): void
    {
        $this->mail[] = $edge;
    }
}
