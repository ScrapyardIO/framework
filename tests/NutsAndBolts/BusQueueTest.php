<?php

use GeneralPurposeIO\Contracts\I2C\I2CException;
use GeneralPurposeIO\Contracts\SPI\SPIException;
use GeneralPurposeIO\NutsAndBolts\BusQueue;
use Voyager\IOPools\EventLoop;

/** A job start: records its name, hands back a promise already resolved with it. */
function namedJob(EventLoop $loop, array &$started, string $name): Closure
{
    return function () use ($loop, &$started, $name) {
        $started[] = $name;
        $promise = $loop->promise();
        $promise->resolve($name);

        return $promise;
    };
}

it('keeps queued jobs waiting while paused, and starts the next one on resume', function () {
    $loop = testLoop();
    $queue = new BusQueue($loop);
    $started = [];

    $queue->pause();
    $a = $queue->push(0, namedJob($loop, $started, 'a'));

    expect($started)->toBe([]);

    $queue->resume();

    expect($started)->toBe(['a'])
        ->and($a->wait())->toBe('a');
});

it('starts nothing until every pause() has its resume()', function () {
    $loop = testLoop();
    $queue = new BusQueue($loop);
    $started = [];

    $queue->pause();
    $queue->pause();
    $queue->push(0, namedJob($loop, $started, 'a'));
    $queue->resume();

    expect($started)->toBe([]);

    $queue->resume();

    expect($started)->toBe(['a']);
});

it('awaitRunning() waits for the running job to settle, and returns at once inside it', function () {
    $loop = testLoop();
    $queue = new BusQueue($loop);
    $running = $loop->promise();
    $inside = null;

    $queue->push(0, function () use ($loop, $queue, $running, &$inside) {
        $loop->async(function () use ($queue, &$inside) {
            $queue->claim(Fiber::getCurrent());
            $queue->awaitRunning();                         // its own job: must not wait for itself
            $inside = $queue->insideRunningJob();
        });

        return $running;
    });
    $loop->at(0.01, fn () => $running->resolve('done'));

    $queue->awaitRunning();

    expect($queue->idle())->toBeTrue()
        ->and($inside)->toBeTrue()
        ->and($queue->insideRunningJob())->toBeFalse();
});

it('names the protocol in the via() refusals every protocol shares', function () {
    expect(I2CException::noEventLoop())->toBeInstanceOf(I2CException::class)
        ->and(SPIException::noEventLoop())->toBeInstanceOf(SPIException::class)
        ->and(SPIException::noEventLoop()->getMessage())->toBe('via() needs an event loop. Boot IOPools, or call the blocking method.')
        ->and(SPIException::noWorkerPools()->getMessage())->toBe('via() offloads to a worker pool, and this driver has no way to find one: make it through its connection manager.');
});
