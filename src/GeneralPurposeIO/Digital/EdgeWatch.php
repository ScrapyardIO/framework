<?php

namespace GeneralPurposeIO\Digital;

use Closure;
use Voyager\Contracts\IOPools\Pumpable;
use Voyager\Contracts\IOPools\StreamWatchable;
use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;

class EdgeWatch implements StreamWatchable, Pumpable
{
    /** @var list<DigitalEdgeEvent> */
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

    public function post(DigitalEdgeEvent $edge): void
    {
        $this->mail[] = $edge;
    }
}