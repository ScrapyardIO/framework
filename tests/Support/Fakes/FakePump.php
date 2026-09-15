<?php

namespace ScrapyardIO\Tests\Support\Fakes;

use Voyager\Contracts\IOPools\PoolPump;
use Voyager\Contracts\IOPools\QueuedIO;

final class FakePump implements PoolPump
{
    /** @var list<QueuedIO> */
    public array $mail = [];

    public function push(QueuedIO $event): void
    {
        $this->mail[] = $event;
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_map(fn (object $m) => $m->name, $this->mail);
    }
}
