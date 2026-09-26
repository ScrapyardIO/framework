<?php

namespace ScrapyardIO\Tests\Fixtures;

/** A port handle that remembers its rate and whether it was closed. */
final class FakeUARTHandle
{
    public bool $closed = false;

    public function __construct(
        public readonly string $device,
        public readonly int $baud,
    ) {}
}
