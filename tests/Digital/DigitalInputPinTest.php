<?php

use GeneralPurposeIO\Contracts\Core\EdgeSource;
use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use GeneralPurposeIO\Contracts\Digital\DigitalIODriver;
use GeneralPurposeIO\Contracts\Digital\SignalEdge;
use GeneralPurposeIO\Digital\DigitalInputPin;
use Microscrap\Bindings\GPIO\DataObjects\GPIOLineRequest;
use ScrapyardIO\Tests\Support\Fakes\FakeDigitalIODriver;

it('listen() returns the driver-produced edge event', function () {
    $driver = new class implements DigitalIODriver
    {
        public function read(GPIOLineRequest|int $pin): bool
        {
            return false;
        }

        public function write(GPIOLineRequest|int $pin, bool $state): bool
        {
            return $state;
        }

        public function listen(int $timeout, bool $rising_events, bool $falling_events, GPIOLineRequest|int $pin): ?DigitalEdgeEvent
        {
            return new DigitalEdgeEvent(SignalEdge::RISING, 5);
        }

        public function pollEdges(GPIOLineRequest|int $pin, bool $rising_events, bool $falling_events, int $max_events = 16): array
        {
            return [];
        }

        public function close(): void
        {
        }
    };

    $pin = new DigitalInputPin(27, $driver);

    expect($pin->listen(true, false, 10))
        ->toBeInstanceOf(DigitalEdgeEvent::class)
        ->edge->toBe(SignalEdge::RISING);
});

it('is an edge source keyed by its offset', function () {
    $driver = new FakeDigitalIODriver();
    $driver->queued = [new DigitalEdgeEvent(SignalEdge::RISING, 5), new DigitalEdgeEvent(SignalEdge::FALLING, 9)];
    $pin = new DigitalInputPin(27, $driver);

    expect($pin)->toBeInstanceOf(EdgeSource::class)
        ->and($pin->offset())->toBe(27)
        ->and($pin->pollEdges(true, true))->toHaveCount(2)
        ->and($driver->polls)->toBe([[27, true, true, 16]])
        ->and($pin->pollEdges())->toBe([]);
});
