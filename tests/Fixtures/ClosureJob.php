<?php

namespace ScrapyardIO\Tests\Fixtures;

use Closure;
use GeneralPurposeIO\Contracts\NutsAndBolts\BusJob;
use GeneralPurposeIO\Contracts\NutsAndBolts\GPIOTransport;

/** A job for in-process tests: runs $body against the slave. Not for a pool: a closure cannot cross a pipe. */
final class ClosureJob implements BusJob
{
    public function __construct(
        private readonly Closure $body,
    ) {}

    public function run(GPIOTransport $bus): mixed
    {
        return ($this->body)($bus);
    }
}
