<?php

use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use GeneralPurposeIO\Contracts\Digital\SignalEdge;
use ScrapyardIO\Tests\Fixtures\RecordingMailHandler;
use Voyager\IOPools\EventLoop;

it('mails every edge of a watched pin as gpio.edge.<device>.<pin>', function () {
    $mail = new RecordingMailHandler;
    $loop = testLoop($mail);
    $pin = socketPin($loop);

    $pin->watch();
    $loop->at(0.005, fn () => $pin->emit('rf'));
    $loop->at(0.03, fn () => $pin->unwatch());
    $loop->run();

    expect($mail->events)->toHaveCount(2)
        ->and($mail->events[0])->toBeInstanceOf(DigitalEdgeEvent::class)
        ->and($mail->events[0]->name())->toBe('gpio.edge.bench.17')
        ->and(array_map(fn ($e) => $e->edge, $mail->events))->toBe([SignalEdge::RISING, SignalEdge::FALLING]);
});

it('mails only the edges watch() asked for', function () {
    $mail = new RecordingMailHandler;
    $loop = testLoop($mail);
    $pin = socketPin($loop);

    $pin->watch(true, false);
    $loop->at(0.005, fn () => $pin->emit('rfr'));
    $loop->at(0.03, fn () => $pin->unwatch());
    $loop->run();

    expect(array_map(fn ($e) => $e->seqno, $mail->events))->toBe([1, 3]);
});

it('a watched edge is also there for listen()', function () {
    $mail = new RecordingMailHandler;
    $loop = testLoop($mail);
    $pin = socketPin($loop);
    $listened = null;

    $pin->watch();
    $loop->at(0.005, fn () => $pin->emit('r'));
    $loop->at(0.03, function () use ($pin, &$listened) {
        $listened = $pin->listen(0, true, true);
        $pin->unwatch();
    });
    $loop->run();

    expect($mail->events)->toHaveCount(1)
        ->and($listened)->toBe($mail->events[0]);
});

it('run() ends once the pin is unwatched', function () {
    $loop = testLoop();
    $pin = socketPin($loop);

    $pin->watch();
    $loop->at(0.01, fn () => $pin->unwatch());

    $start = microtime(true);
    $loop->run();

    expect(microtime(true) - $start)->toBeLessThan(0.1);
});

it('a listen() on a watched pin leaves the watch in place', function () {
    $mail = new RecordingMailHandler;
    $loop = testLoop($mail);
    $pin = socketPin($loop);

    $pin->watch();
    $loop->at(0.002, fn () => $pin->emit('r'));
    $loop->at(0.001, function () use ($loop, $pin) {
        $pin->listen(500, true, true);
        $loop->at(0.005, fn () => $pin->emit('f'));
        $loop->at(0.03, fn () => $pin->unwatch());
    });
    $loop->run();

    expect(array_map(fn ($e) => $e->edge, $mail->events))->toBe([SignalEdge::RISING, SignalEdge::FALLING]);
});

it('a sampled pin mails its level changes and stops sampling once unwatched', function () {
    $mail = new RecordingMailHandler;
    $loop = testLoop($mail);
    $pin = sampledPin($loop, [false, false, true, true, false]);

    $pin->watch();
    $loop->at(0.05, fn () => $pin->unwatch());

    $start = microtime(true);
    $loop->run();
    $samples = $pin->samples;

    expect(array_map(fn ($e) => $e->edge, $mail->events))->toBe([SignalEdge::RISING, SignalEdge::FALLING])
        ->and(microtime(true) - $start)->toBeLessThan(0.2);

    $done = false;
    $loop->at(0.02, function () use (&$done) { $done = true; });
    $loop->until(function () use (&$done) { return $done; });

    expect($pin->samples)->toBe($samples);
});
