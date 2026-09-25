<?php

use GeneralPurposeIO\Contracts\Digital\DigitalIOException;
use Voyager\IOPools\EventLoop;

it('close() wakes a waiting listen() with an exception', function () {
    $loop = new EventLoop;
    $pin = socketPin($loop);

    $listener = $loop->async(fn () => $pin->listen(-1, true, true));
    $loop->at(0.005, fn () => $pin->close());
    $loop->run();

    expect(fn () => $loop->await($listener))->toThrow(DigitalIOException::class, 'closed')
        ->and($pin->closed())->toBeTrue()
        ->and($pin->released)->toBeTrue();
});

it('close() takes a watched pin off the loop, so run() ends', function () {
    $loop = new EventLoop;
    $pin = socketPin($loop);

    $pin->watch();
    $loop->at(0.01, fn () => $pin->close());

    $start = microtime(true);
    $loop->run();

    expect(microtime(true) - $start)->toBeLessThan(0.1);
});

it('a closed pin refuses every call but a second close()', function () {
    $pin = socketPin(new EventLoop);
    $pin->close();
    $pin->close();

    expect(fn () => $pin->read())->toThrow(DigitalIOException::class, 'closed')
        ->and(fn () => $pin->listen(0, true, true))->toThrow(DigitalIOException::class, 'closed')
        ->and(fn () => $pin->pollEdges(true, true))->toThrow(DigitalIOException::class, 'closed')
        ->and(fn () => $pin->watch())->toThrow(DigitalIOException::class, 'closed');
});
