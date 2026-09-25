<?php

use GeneralPurposeIO\Contracts\Digital\SignalEdge;
use Voyager\IOPools\EventLoop;

it('listen() from a timer callback keeps the rest of the loop turning', function () {
    $loop = new EventLoop;
    $pin = socketPin($loop);
    $beats = 0;
    $beats_while_listening = 0;
    $edge = null;

    $heartbeat = $loop->every(0.002, function () use (&$beats) { $beats++; }, 'heartbeat');

    $loop->at(0.001, function () use ($loop, $pin, $heartbeat, &$beats, &$beats_while_listening, &$edge) {
        $loop->at(0.04, fn () => $pin->emit('r'));

        $before = $beats;
        $edge = $pin->listen(1000, true, true);
        $beats_while_listening = $beats - $before;

        $heartbeat->cancel();
    });

    $loop->run();

    expect($edge?->edge)->toBe(SignalEdge::RISING)
        ->and($beats_while_listening)->toBeGreaterThan(5);
});

it('listen() inside async() suspends while other fibers run', function () {
    $loop = new EventLoop;
    $pin = socketPin($loop);
    $steps = 0;

    $listener = $loop->async(fn () => $pin->listen(-1, true, true));

    $loop->async(function () use ($loop, &$steps) {
        for ($i = 0; $i < 3; $i++) {
            $done = false;
            $loop->at(0.002, function () use (&$done) { $done = true; });
            $loop->until(function () use (&$done) { return $done; });
            $steps++;
        }
    });

    $loop->at(0.03, fn () => $pin->emit('f'));
    $loop->run();

    expect($steps)->toBe(3)
        ->and($loop->await($listener)->edge)->toBe(SignalEdge::FALLING);
});

it('a timeout with no other timer returns null and leaves nothing registered', function () {
    $loop = new EventLoop;
    $pin = socketPin($loop);

    $start = microtime(true);
    $edge = $pin->listen(20, true, true);
    $waited = microtime(true) - $start;

    $start = microtime(true);
    $loop->run();

    expect($edge)->toBeNull()
        ->and($waited)->toBeGreaterThanOrEqual(0.019)
        ->and(microtime(true) - $start)->toBeLessThan(0.01);
});

it('an unwanted edge stays queued for the listen() that wants it', function () {
    $loop = new EventLoop;
    $pin = socketPin($loop);

    $loop->at(0.005, fn () => $pin->emit('fr'));

    $rising = $pin->listen(500, true, false);
    $falling = $pin->listen(0, false, true);

    expect($rising?->seqno)->toBe(2)
        ->and($falling?->seqno)->toBe(1);
});

it('two waiters on one pin get two different edges', function () {
    $loop = new EventLoop;
    $pin = socketPin($loop);

    $a = $loop->async(fn () => $pin->listen(-1, true, true));
    $b = $loop->async(fn () => $pin->listen(-1, true, true));

    $loop->at(0.01, fn () => $pin->emit('rf'));
    $loop->run();

    $seqnos = [$loop->await($a)->seqno, $loop->await($b)->seqno];
    sort($seqnos);

    expect($seqnos)->toBe([1, 2]);
});

it('edges that arrive between two listen() calls wait for the second', function () {
    $loop = new EventLoop;
    $pin = socketPin($loop);

    $loop->at(0.002, fn () => $pin->emit('r'));
    $first = $pin->listen(500, true, true);

    $pin->emit('f');
    $done = false;
    $loop->at(0.01, function () use (&$done) { $done = true; });
    $loop->until(function () use (&$done) { return $done; });

    expect($first?->edge)->toBe(SignalEdge::RISING)
        ->and($pin->listen(0, true, true)?->edge)->toBe(SignalEdge::FALLING);
});

it('a sampled pin is sampled on its timer during a listen()', function () {
    $loop = new EventLoop;
    $pin = sampledPin($loop, [false, false, true]);

    $start = microtime(true);
    $edge = $pin->listen(500, true, true);

    expect($edge?->edge)->toBe(SignalEdge::RISING)
        ->and(microtime(true) - $start)->toBeLessThan(0.1);
});
