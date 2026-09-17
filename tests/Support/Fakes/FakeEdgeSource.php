<?php

namespace ScrapyardIO\Tests\Support\Fakes;

use GeneralPurposeIO\Contracts\Core\EdgeSource;
use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use Throwable;

final class FakeEdgeSource implements EdgeSource
{
    /** @var list<DigitalEdgeEvent> */
    public array $queued = [];

    /** @var list<array{bool, bool}> */
    public array $polls = [];

    public ?Throwable $fault = null;

    public function __construct(public readonly int $offset, public readonly string|int|null $device = null) {}

    public function device(): string|int|null { return $this->device; }

    public function offset(): int { return $this->offset; }

    public function pollEdges(bool $rising, bool $falling): array
    {
        $this->polls[] = [$rising, $falling];

        if (! is_null($this->fault)) {
            throw $this->fault;
        }

        $out = $this->queued;
        $this->queued = [];

        return $out;
    }
}
