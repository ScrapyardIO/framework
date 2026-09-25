<?php

namespace GeneralPurposeIO\NutsAndBolts;

use GeneralPurposeIO\Contracts\NutsAndBolts\BusJob;
use GeneralPurposeIO\Contracts\NutsAndBolts\GPIOTransport;

/** One of the transport's own blocking methods, as a job. */
final class TransportCall implements BusJob
{
    public function __construct(
        public readonly string $method,
        public readonly array $args = [],
    ) {}

    public function run(GPIOTransport $bus): mixed
    {
        return $bus->{$this->method}(...$this->args);
    }
}
