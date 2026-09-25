<?php

use GeneralPurposeIO\Contracts\Digital\DigitalIOException;
use GeneralPurposeIO\Contracts\Digital\SignalEdge;

it('returns an edge already queued without waiting', function () {
    $pin = socketPin(null);
    $pin->emit('r');

    $start = microtime(true);
    $edge = $pin->listen(-1, true, true);

    expect($edge->edge)->toBe(SignalEdge::RISING)
        ->and($edge->device)->toBe('bench')
        ->and($edge->pin)->toBe(17)
        ->and(microtime(true) - $start)->toBeLessThan(0.05);
});

it('waits out the timeout when only unwanted edges arrive, and keeps them for later', function () {
    $pin = socketPin(null);
    $pin->emit('f');

    $start = microtime(true);
    $rising = $pin->listen(30, true, false);
    $waited = microtime(true) - $start;

    expect($rising)->toBeNull()
        ->and($waited)->toBeGreaterThanOrEqual(0.029)
        ->and($pin->listen(0, false, true)?->edge)->toBe(SignalEdge::FALLING);
});

it('listen(0) never waits', function () {
    $pin = socketPin(null);

    $start = microtime(true);

    expect($pin->listen(0, true, true))->toBeNull()
        ->and(microtime(true) - $start)->toBeLessThan(0.01);
});

it('pollEdges() takes every matching edge, oldest first, and leaves the rest', function () {
    $pin = socketPin(null);
    $pin->emit('rfrf');

    $rising = $pin->pollEdges(true, false);

    expect(array_map(fn ($e) => $e->seqno, $rising))->toBe([1, 3])
        ->and(array_map(fn ($e) => $e->seqno, $pin->pollEdges(true, true)))->toBe([2, 4]);
});

it('keeps the newest 64 unread edges and drops the oldest', function () {
    $pin = socketPin(null);
    $pin->emit(str_repeat('r', 70));

    $edges = $pin->pollEdges(true, true);

    expect($edges)->toHaveCount(64)
        ->and($edges[0]->seqno)->toBe(7)
        ->and(end($edges)->seqno)->toBe(70);
});

it('a sampled pin waits for a change instead of giving up at once', function () {
    $pin = sampledPin(null, [false, false, false, true]);

    $edge = $pin->listen(-1, true, true);

    expect($edge->edge)->toBe(SignalEdge::RISING)
        ->and($pin->samples)->toBe(4);
});

it('watch() needs an event loop', function () {
    expect(fn () => socketPin(null)->watch())->toThrow(DigitalIOException::class, 'No Event Loop');
});
