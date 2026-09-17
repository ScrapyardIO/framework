<?php

use GeneralPurposeIO\Contracts\Core\Mail\DigitalEdgeOccurrence;
use GeneralPurposeIO\Contracts\Core\Mail\SourceFaultOccurrence;
use GeneralPurposeIO\Contracts\Core\Mail\TransferCompletion;
use GeneralPurposeIO\Contracts\Core\Mail\UARTBytesOccurrence;
use GeneralPurposeIO\Contracts\Core\Recurrence;
use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use GeneralPurposeIO\Contracts\Digital\SignalEdge;
use GeneralPurposeIO\Contracts\NutsAndBolts\GPIOException;
use GeneralPurposeIO\Core\IOPools\GPIOResourceDriver;
use ScrapyardIO\Tests\Support\Fakes\FakeByteSource;
use ScrapyardIO\Tests\Support\Fakes\FakeEdgeSource;
use ScrapyardIO\Tests\Support\Fakes\FakePump;
use Voyager\Contracts\IOPools\IOResourceDriver;
use Voyager\Contracts\IOPools\Occurrence;
use Voyager\IOPools\Presumption;

/*
| The gpio dock resource, proven dry: a fake pump collects mail, fake sources
| hand over what they buffered. Five verbs: watch, receive, defer, every,
| stream. One law: tick never waits.
*/

function gpioResource(?int $defer_per_tick = null): array
{
    $pump = new FakePump;

    return [new GPIOResourceDriver($pump, $defer_per_tick), $pump];
}

it('is a dock resource', function (): void {
    [$gpio] = gpioResource();

    expect($gpio)->toBeInstanceOf(IOResourceDriver::class);
});

// --- watch ---------------------------------------------------------------

it('pushes one edge occurrence per polled edge, named by device and offset, with the watch flags', function (): void {
    [$gpio, $pump] = gpioResource();
    $pin = new FakeEdgeSource(17, 0);
    $pin->queued = [new DigitalEdgeEvent(SignalEdge::RISING, 1_000), new DigitalEdgeEvent(SignalEdge::FALLING, 2_000)];

    $gpio->watch($pin, rising: true, falling: true)->tick();

    expect($pin->polls)->toBe([[true, true]])
        ->and($pump->names())->toBe(['gpio.edge.0.17', 'gpio.edge.0.17'])
        ->and($pump->mail[0])->toBeInstanceOf(DigitalEdgeOccurrence::class)
        ->and($pump->mail[0]->edge)->toBe(SignalEdge::RISING)
        ->and($pump->mail[1]->timestamp_ns)->toBe(2_000);
});

it('watches rising only by default', function (): void {
    [$gpio] = gpioResource();
    $pin = new FakeEdgeSource(17);

    $gpio->watch($pin)->tick();

    expect($pin->polls)->toBe([[true, false]]);
});

it('stops polling an unwatched pin', function (): void {
    [$gpio, $pump] = gpioResource();
    $pin = new FakeEdgeSource(17);

    $gpio->watch($pin)->unwatch($pin)->tick();

    expect($pin->polls)->toBe([])
        ->and($pump->mail)->toBe([]);
});

it('reports a faulting pin as mail and keeps polling it', function (): void {
    [$gpio, $pump] = gpioResource();
    $pin = new FakeEdgeSource(17, 'ft232h');
    $pin->fault = new RuntimeException('usb gone');

    $gpio->watch($pin)->tick();
    $gpio->tick();

    expect($pump->names())->toBe(['gpio.fault.edge.ft232h.17', 'gpio.fault.edge.ft232h.17'])
        ->and($pump->mail[0])->toBeInstanceOf(SourceFaultOccurrence::class)
        ->and($pump->mail[0]->error->getMessage())->toBe('usb gone')
        ->and($pin->polls)->toHaveCount(2);
});

// --- receive -------------------------------------------------------------

it('pushes uart bytes only when something was buffered, capped per tick', function (): void {
    [$gpio, $pump] = gpioResource();
    $port = new FakeByteSource('/dev/ttyAMA0');
    $port->buffered = 'hello';

    $gpio->receive($port, max_bytes: 3)->tick();
    $gpio->tick();
    $gpio->tick();

    expect($pump->names())->toBe(['gpio.uart./dev/ttyAMA0', 'gpio.uart./dev/ttyAMA0'])
        ->and($pump->mail[0])->toBeInstanceOf(UARTBytesOccurrence::class)
        ->and($pump->mail[0]->bytes)->toBe('hel')
        ->and($pump->mail[1]->bytes)->toBe('lo');
});

it('stops draining a port after stopReceiving', function (): void {
    [$gpio, $pump] = gpioResource();
    $port = new FakeByteSource('/dev/ttyAMA0');
    $port->buffered = 'x';

    $gpio->receive($port)->stopReceiving($port)->tick();

    expect($pump->mail)->toBe([])
        ->and($port->buffered)->toBe('x');
});

it('reports a faulting port as mail and keeps going', function (): void {
    [$gpio, $pump] = gpioResource();
    $port = new FakeByteSource('/dev/ttyAMA0');
    $port->fault = new RuntimeException('EIO');

    $gpio->receive($port)->tick();

    expect($pump->names())->toBe(['gpio.fault.uart./dev/ttyAMA0']);
});

// --- defer ---------------------------------------------------------------

it('runs deferred work on the next tick and settles the presumption on both lanes', function (): void {
    [$gpio, $pump] = gpioResource();
    $seen = null;

    $presumption = $gpio->defer('aht20.measure', fn (): array => [21, 55])
        ->onSuccess(function (TransferCompletion $c) use (&$seen): void { $seen = $c->result; });

    expect($presumption)->toBeInstanceOf(Presumption::class)
        ->and($presumption->settled())->toBeFalse()
        ->and($gpio->inFlight('aht20.measure'))->toBe($presumption)
        ->and($pump->mail)->toBe([]);

    $gpio->tick();

    expect($presumption->settled())->toBeTrue()
        ->and($seen)->toBe([21, 55])
        ->and($pump->names())->toBe(['gpio.transfer.aht20.measure'])
        ->and($pump->mail[0]->result)->toBe([21, 55])
        ->and($gpio->inFlight('aht20.measure'))->toBeNull();
});

it('turns a throwing closure into a failed completion, never an exception out of tick', function (): void {
    [$gpio, $pump] = gpioResource();
    $failed = null;

    $gpio->defer('bad', fn () => throw new RuntimeException('nack'))
        ->onFail(function (TransferCompletion $c) use (&$failed): void { $failed = $c; });

    $gpio->tick();

    expect($failed)->toBeInstanceOf(TransferCompletion::class)
        ->and($failed->ok())->toBeFalse()
        ->and($failed->error->getMessage())->toBe('nack')
        ->and($pump->mail[0]->ok())->toBeFalse();
});

it('refuses a duplicate name while one is in flight, and frees it on settle', function (): void {
    [$gpio] = gpioResource();
    $gpio->defer('x', fn () => 1);

    expect(fn () => $gpio->defer('x', fn () => 2))->toThrow(GPIOException::class, 'already in flight');

    $gpio->tick();
    $gpio->defer('x', fn () => 3);

    expect($gpio->inFlight('x'))->not->toBeNull();
});

it('swaps domain mail in through the envelope but settles on the raw completion', function (): void {
    [$gpio, $pump] = gpioResource();
    $domain = new class implements Occurrence { public string $name = 'sensor.temperature'; };
    $settled = null;

    $gpio->defer('t', fn () => 21, fn (TransferCompletion $c) => $domain)
        ->onSuccess(function (TransferCompletion $c) use (&$settled): void { $settled = $c; });
    $gpio->tick();

    expect($pump->mail)->toBe([$domain])
        ->and($settled)->toBeInstanceOf(TransferCompletion::class)
        ->and($settled->result)->toBe(21);
});

it('falls back to the raw completion when the envelope throws or returns non-mail', function (): void {
    [$gpio, $pump] = gpioResource();

    $gpio->defer('a', fn () => 1, fn () => throw new LogicException('bad envelope'));
    $gpio->defer('b', fn () => 2, fn () => 'not mail');
    $gpio->tick();

    expect($pump->names())->toBe(['gpio.transfer.a', 'gpio.transfer.b'])
        ->and($pump->mail[0])->toBeInstanceOf(TransferCompletion::class)
        ->and($pump->mail[1]->result)->toBe(2);
});

it('runs the queue FIFO and honours a per-tick cap', function (): void {
    [$gpio, $pump] = gpioResource(defer_per_tick: 2);
    $gpio->defer('a', fn () => 'a');
    $gpio->defer('b', fn () => 'b');
    $gpio->defer('c', fn () => 'c');

    $gpio->tick();
    expect($pump->names())->toBe(['gpio.transfer.a', 'gpio.transfer.b']);

    $gpio->tick();
    expect($pump->names())->toBe(['gpio.transfer.a', 'gpio.transfer.b', 'gpio.transfer.c']);
});

it('a hook that re-defers runs once per tick', function (): void {
    [$gpio, $pump] = gpioResource();
    $again = function () use ($gpio, &$again): void {
        $gpio->defer('loop', fn () => 'x')->onSuccess($again);
    };
    $again();

    $gpio->tick();
    $gpio->tick();
    $gpio->tick();

    expect($pump->names())->toBe(['gpio.transfer.loop', 'gpio.transfer.loop', 'gpio.transfer.loop']);
});

it('rejects a defer budget below one', function (): void {
    expect(fn () => new GPIOResourceDriver(new FakePump, 0))->toThrow(GPIOException::class, 'defer_per_tick');
});

// --- every ---------------------------------------------------------------

it('runs a recurrence every tick by default and pushes a completion each run', function (): void {
    [$gpio, $pump] = gpioResource();
    $count = 0;
    $seen = [];

    $recurrence = $gpio->every('gamepad.poll', function () use (&$count): int { return ++$count; })
        ->onEach(function (TransferCompletion $c) use (&$seen): void { $seen[] = $c->result; });

    expect($recurrence)->toBeInstanceOf(Recurrence::class)
        ->and($recurrence->name)->toBe('gamepad.poll')
        ->and($gpio->recurring('gamepad.poll'))->toBe($recurrence)
        ->and($pump->mail)->toBe([]);

    $gpio->tick();
    $gpio->tick();
    $gpio->tick();

    expect($seen)->toBe([1, 2, 3])
        ->and($recurrence->runs())->toBe(3)
        ->and($pump->names())->toBe(['gpio.transfer.gamepad.poll', 'gpio.transfer.gamepad.poll', 'gpio.transfer.gamepad.poll']);
});

it('honours a cadence in ticks, counting from registration', function (): void {
    [$gpio, $pump] = gpioResource();
    $gpio->every('adxl.sample', fn () => 'g', ticks: 3);

    foreach (range(1, 7) as $tick) {
        $gpio->tick();
    }

    expect($pump->names())->toHaveCount(2);
});

it('rejects a cadence below one tick and a duplicate name', function (): void {
    [$gpio] = gpioResource();
    $gpio->every('x', fn () => 1);

    expect(fn () => $gpio->every('y', fn () => 1, ticks: 0))->toThrow(GPIOException::class, 'ticks')
        ->and(fn () => $gpio->every('x', fn () => 1))->toThrow(GPIOException::class, 'already recurring');
});

it('reports a throwing run as fault mail, hands the error to onFail, and keeps recurring', function (): void {
    [$gpio, $pump] = gpioResource();
    $runs = 0;
    $failures = [];

    $gpio->every('flaky', function () use (&$runs): int {
        if (++$runs === 2) {
            throw new RuntimeException('nack');
        }

        return $runs;
    })->onFail(function (Throwable $e) use (&$failures): void { $failures[] = $e->getMessage(); });

    $gpio->tick();
    $gpio->tick();
    $gpio->tick();

    expect($pump->names())->toBe(['gpio.transfer.flaky', 'gpio.fault.every.flaky', 'gpio.transfer.flaky'])
        ->and($failures)->toBe(['nack'])
        ->and($runs)->toBe(3);
});

it('stops when told, including from inside its own hook, and frees the name', function (): void {
    [$gpio, $pump] = gpioResource();
    $recurrence = $gpio->every('once-or-twice', fn () => 'r');
    $recurrence->onEach(function (TransferCompletion $c) use ($recurrence): void {
        if ($recurrence->runs() === 2) {
            $recurrence->stop();
        }
    });

    foreach (range(1, 5) as $tick) {
        $gpio->tick();
    }

    expect($pump->names())->toHaveCount(2)
        ->and($recurrence->stopped())->toBeTrue()
        ->and($gpio->recurring('once-or-twice'))->toBeNull()
        ->and($gpio->every('once-or-twice', fn () => 'again'))->toBeInstanceOf(Recurrence::class);
});

it('a recurrence registered during a tick first runs on the next tick', function (): void {
    [$gpio, $pump] = gpioResource();
    $gpio->defer('setup', function () use ($gpio): void {
        $gpio->every('later', fn () => 'l');
    });

    $gpio->tick();
    expect($pump->names())->toBe(['gpio.transfer.setup']);

    $gpio->tick();
    expect($pump->names())->toBe(['gpio.transfer.setup', 'gpio.transfer.later']);
});

// --- stream --------------------------------------------------------------

it('sends one chunk per tick through the writer, reports progress, and completes with the byte count', function (): void {
    [$gpio, $pump] = gpioResource();
    $sent = [];
    $progress = [];
    $done = null;

    $presumption = $gpio->stream('ssd1306.frame', function (string $chunk) use (&$sent): void { $sent[] = $chunk; }, 'abcdefg', chunk: 3)
        ->onProgress(function (int $now, int $total) use (&$progress): void { $progress[] = [$now, $total]; })
        ->onSuccess(function (TransferCompletion $c) use (&$done): void { $done = $c->result; });

    expect($presumption)->toBeInstanceOf(Presumption::class)
        ->and($gpio->streaming('ssd1306.frame'))->toBe($presumption);

    $gpio->tick();
    expect($sent)->toBe(['abc'])->and($pump->mail)->toBe([])->and($presumption->settled())->toBeFalse();

    $gpio->tick();
    $gpio->tick();

    expect($sent)->toBe(['abc', 'def', 'g'])
        ->and($progress)->toBe([[3, 7], [6, 7], [7, 7]])
        ->and($done)->toBe(7)
        ->and($pump->names())->toBe(['gpio.transfer.ssd1306.frame'])
        ->and($gpio->streaming('ssd1306.frame'))->toBeNull();
});

it('completes an empty stream on the next tick without writing', function (): void {
    [$gpio, $pump] = gpioResource();
    $writes = 0;

    $presumption = $gpio->stream('empty', function () use (&$writes): void { $writes++; }, '', chunk: 16);
    $gpio->tick();

    expect($writes)->toBe(0)
        ->and($presumption->settled())->toBeTrue()
        ->and($pump->mail[0]->result)->toBe(0);
});

it('fails the stream on a throwing writer with the bytes sent so far, and frees the name', function (): void {
    [$gpio, $pump] = gpioResource();
    $calls = 0;
    $failed = null;

    $gpio->stream('flaky', function () use (&$calls): void {
        if (++$calls === 2) {
            throw new RuntimeException('usb gone');
        }
    }, 'abcdef', chunk: 2)->onFail(function (TransferCompletion $c) use (&$failed): void { $failed = $c; });

    $gpio->tick();
    $gpio->tick();
    $gpio->tick();

    expect($calls)->toBe(2)
        ->and($failed->ok())->toBeFalse()
        ->and($failed->result)->toBe(2)
        ->and($failed->error->getMessage())->toBe('usb gone')
        ->and($pump->names())->toBe(['gpio.transfer.flaky'])
        ->and($gpio->streaming('flaky'))->toBeNull();
});

it('rejects a chunk below one byte and a duplicate stream name', function (): void {
    [$gpio] = gpioResource();
    $gpio->stream('x', fn () => null, 'abc', chunk: 1);

    expect(fn () => $gpio->stream('y', fn () => null, 'abc', chunk: 0))->toThrow(GPIOException::class, 'chunk')
        ->and(fn () => $gpio->stream('x', fn () => null, 'abc', chunk: 1))->toThrow(GPIOException::class, 'already streaming');
});

// --- tick order ----------------------------------------------------------

it('ticks sources first, then deferred work, then recurrences, then streams', function (): void {
    [$gpio, $pump] = gpioResource();
    $pin = new FakeEdgeSource(17, 0);
    $pin->queued = [new DigitalEdgeEvent(SignalEdge::RISING, 1)];
    $port = new FakeByteSource('/dev/ttyAMA0');
    $port->buffered = 'x';

    $gpio->watch($pin)->receive($port);
    $gpio->stream('s', fn () => null, 'ab', chunk: 2);
    $gpio->every('e', fn () => 'e');
    $gpio->defer('d', fn () => 'd');

    $gpio->tick();

    expect($pump->names())->toBe(['gpio.edge.0.17', 'gpio.uart./dev/ttyAMA0', 'gpio.transfer.d', 'gpio.transfer.e', 'gpio.transfer.s']);
});
