<?php

namespace ScrapyardIO\Tests\Fixtures;

/** A chip handle that remembers whether the driver closed it. */
final class FakePWMHandle
{
    public bool $closed = false;

    public function __construct(
        public readonly string|int $device,
    ) {}
}
