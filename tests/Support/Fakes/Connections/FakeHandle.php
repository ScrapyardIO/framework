<?php

namespace ScrapyardIO\Tests\Support\Fakes\Connections;

/** What a fake factory "opens": nothing, but it remembers which device it stands for. */
final class FakeHandle
{
    public bool $closed = false;

    public function __construct(public readonly string|int $device) {}
}
