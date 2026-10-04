<?php

namespace ScrapyardIO\Tests\Fixtures;

/** A bus handle that remembers whether the driver closed it. */
final class FakeI2CHandle
{
    public bool $closed = false;

    public function __construct(
        public readonly string|int $device,
    ) {}
}
