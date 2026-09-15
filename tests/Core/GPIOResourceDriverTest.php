<?php

use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\Contracts\Core\GPIOResourceDriver as Contract;
use GeneralPurposeIO\Contracts\Core\Mail\DigitalEdgeOccurrence;
use GeneralPurposeIO\Contracts\Core\Mail\SourceFaultOccurrence;
use GeneralPurposeIO\Contracts\Core\Mail\TransferCompletion;
use GeneralPurposeIO\Contracts\Core\Mail\UARTBytesOccurrence;
use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use GeneralPurposeIO\Contracts\Digital\SignalEdge;
use GeneralPurposeIO\Core\IOPools\GPIOResourceDriver;
use ScrapyardIO\Tests\Support\Fakes\FakeByteSource;
use ScrapyardIO\Tests\Support\Fakes\FakeEdgeSource;
use ScrapyardIO\Tests\Support\Fakes\FakePump;
use Voyager\Contracts\IOPools\IOResourceDriver;
use Voyager\IOPools\Presumption;

beforeEach(function () {
    $this->pump = new FakePump();
    $this->gpio = new GPIOResourceDriver($this->pump);
});

it('is a dock resource', function () {
    expect($this->gpio)->toBeInstanceOf(Contract::class)->toBeInstanceOf(IOResourceDriver::class);
});

it('pushes one edge occurrence per polled edge, with the watch flags', function () {
    $pin = new FakeEdgeSource(17);
    $pin->queued = [new DigitalEdgeEvent(SignalEdge::RISING, 100), new DigitalEdgeEvent(SignalEdge::FALLING, 200)];
    $this->gpio->watch($pin, rising: true, falling: true);

    $this->gpio->tick();

    expect($this->pump->names())->toBe(['gpio.edge.17', 'gpio.edge.17'])
        ->and($this->pump->mail[0])->toBeInstanceOf(DigitalEdgeOccurrence::class)
        ->and($this->pump->mail[0]->edge)->toBe(SignalEdge::RISING)
        ->and($this->pump->mail[1]->timestamp_ns)->toBe(200)
        ->and($pin->polls)->toBe([[true, true]]);
});

it('stops polling an unwatched pin', function () {
    $pin = new FakeEdgeSource(4);
    $this->gpio->watch($pin)->unwatch($pin);

    $this->gpio->tick();

    expect($pin->polls)->toBe([])->and($this->pump->mail)->toBe([]);
});

it('pushes uart bytes only when something was buffered', function () {
    $port = new FakeByteSource('/dev/ttyAMA0');
    $this->gpio->receive($port, 4);

    $this->gpio->tick();
    $port->buffered = 'abcdef';
    $this->gpio->tick();
    $this->gpio->tick();
    $this->gpio->stopReceiving($port);
    $port->buffered = 'zzz';
    $this->gpio->tick();

    expect($this->pump->names())->toBe(['gpio.uart./dev/ttyAMA0', 'gpio.uart./dev/ttyAMA0'])
        ->and($this->pump->mail[0])->toBeInstanceOf(UARTBytesOccurrence::class)
        ->and($this->pump->mail[0]->bytes)->toBe('abcd')
        ->and($this->pump->mail[1]->bytes)->toBe('ef');
});

it('runs deferred work on the next tick and settles the presumption', function () {
    $seen = null;
    $presumption = $this->gpio->defer('aht20.measure', fn () => [1, 2, 3])->onSuccess(function (TransferCompletion $c) use (&$seen) {
        $seen = $c->result;
    });

    expect($presumption)->toBeInstanceOf(Presumption::class)
        ->and($presumption->settled())->toBeFalse()
        ->and($this->gpio->inFlight('aht20.measure'))->toBe($presumption)
        ->and($this->pump->mail)->toBe([]);

    $this->gpio->tick();

    expect($seen)->toBe([1, 2, 3])
        ->and($presumption->settled())->toBeTrue()
        ->and($this->pump->names())->toBe(['gpio.transfer.aht20.measure'])
        ->and($this->pump->mail[0]->ok())->toBeTrue()
        ->and($this->gpio->inFlight('aht20.measure'))->toBeNull();
});

it('turns a throwing closure into a failed completion, never an exception out of tick', function () {
    $failed = null;
    $behind = null;
    $this->gpio->defer('boom', fn () => throw new RuntimeException('bus fault'))->onFail(function (TransferCompletion $c) use (&$failed) {
        $failed = $c->error?->getMessage();
    });
    $this->gpio->defer('after-boom', fn () => 'still-fine')->onSuccess(function (TransferCompletion $c) use (&$behind) {
        $behind = $c->result;
    });

    $this->gpio->tick();

    expect($failed)->toBe('bus fault')
        ->and($this->pump->mail[0]->ok())->toBeFalse()
        ->and($this->gpio->inFlight('boom'))->toBeNull()
        ->and($behind)->toBe('still-fine')
        ->and($this->pump->mail[1]->ok())->toBeTrue();
});

it('refuses a duplicate name while one is in flight, and frees it on settle', function () {
    $this->gpio->defer('x', fn () => 1);

    expect(fn () => $this->gpio->defer('x', fn () => 2))->toThrow(GPIOException::class, 'Transfer [x] is already in flight');

    $this->gpio->tick();
    $this->gpio->defer('x', fn () => 3);
    $this->gpio->tick();

    expect(array_map(fn ($m) => $m->result, $this->pump->mail))->toBe([1, 3]);
});

it('swaps domain mail in through the envelope but settles on the raw completion', function () {
    $raw = null;
    $custom = new class('gpio.custom') implements Voyager\Contracts\IOPools\Occurrence {
        public function __construct(public readonly string $name) {}
    };
    $this->gpio->defer('y', fn () => 'v', fn (TransferCompletion $c) => $custom)->onSuccess(function (TransferCompletion $c) use (&$raw) {
        $raw = $c;
    });

    $this->gpio->tick();

    expect($this->pump->mail)->toBe([$custom])->and($raw?->result)->toBe('v');
});

it('runs the queue FIFO and honours a per-tick cap', function () {
    $gpio = new GPIOResourceDriver($this->pump, 2);
    foreach (['a', 'b', 'c'] as $n) {
        $gpio->defer($n, fn () => $n);
    }

    $gpio->tick();
    expect(array_map(fn ($m) => $m->result, $this->pump->mail))->toBe(['a', 'b'])
        ->and($gpio->inFlight('c'))->not->toBeNull();

    $gpio->tick();
    expect(array_map(fn ($m) => $m->result, $this->pump->mail))->toBe(['a', 'b', 'c']);
});

it('a hook that re-defers runs once per tick', function () {
    $runs = 0;
    $rearm = function () use (&$rearm, &$runs) {
        $runs++;

        if ($runs < 5) {
            $this->gpio->defer('poll', fn () => 1)->onSuccess($rearm);
        }
    };
    $this->gpio->defer('poll', fn () => 1)->onSuccess($rearm);

    $this->gpio->tick();

    expect($this->pump->names())->toBe(['gpio.transfer.poll'])
        ->and($this->gpio->inFlight('poll'))->not->toBeNull();

    $this->gpio->tick();

    expect($this->pump->names())->toBe(['gpio.transfer.poll', 'gpio.transfer.poll']);
});

it('falls back to the raw completion when the envelope throws', function () {
    $raw = null;
    $this->gpio->defer('y', fn () => 'v', fn (TransferCompletion $c) => throw new RuntimeException('bad envelope'))
        ->onSuccess(function (TransferCompletion $c) use (&$raw) {
            $raw = $c;
        });

    $this->gpio->tick();

    expect($this->pump->mail[0])->toBeInstanceOf(TransferCompletion::class)
        ->and($this->pump->mail[0]->result)->toBe('v')
        ->and($raw?->result)->toBe('v')
        ->and($this->gpio->inFlight('y'))->toBeNull();
});

it('falls back to the raw completion when the envelope returns a non-QueuedIO', function () {
    $raw = null;
    $this->gpio->defer('y', fn () => 'v', fn (TransferCompletion $c) => 'oops')
        ->onSuccess(function (TransferCompletion $c) use (&$raw) {
            $raw = $c;
        });

    $this->gpio->tick();

    expect($this->pump->mail[0])->toBeInstanceOf(TransferCompletion::class)
        ->and($this->pump->mail[0]->result)->toBe('v')
        ->and($raw?->result)->toBe('v')
        ->and($this->gpio->inFlight('y'))->toBeNull();
});

it('reports a faulting source as mail and keeps going', function () {
    $badPin = new FakeEdgeSource(9);
    $badPin->fault = new RuntimeException('EIO');
    $goodPin = new FakeEdgeSource(3);
    $goodPin->queued = [new DigitalEdgeEvent(SignalEdge::RISING, 50)];
    $port = new FakeByteSource('/dev/ttyAMA0');
    $port->buffered = 'hi';

    $this->gpio->watch($badPin)->watch($goodPin)->receive($port);
    $this->gpio->defer('measure', fn () => 42);

    $this->gpio->tick();

    expect($this->pump->names())->toBe([
        'gpio.fault.edge.9',
        'gpio.edge.3',
        'gpio.uart./dev/ttyAMA0',
        'gpio.transfer.measure',
    ])
        ->and($this->pump->mail[0])->toBeInstanceOf(SourceFaultOccurrence::class)
        ->and($this->pump->mail[0]->error->getMessage())->toBe('EIO');
});

it('reports a faulting byte source as mail and keeps going', function () {
    $port = new FakeByteSource('/dev/ttyAMA0');
    $port->fault = new RuntimeException('EIO');

    $this->gpio->receive($port);

    $this->gpio->tick();

    expect($this->pump->names())->toBe(['gpio.fault.uart./dev/ttyAMA0'])
        ->and($this->pump->mail[0])->toBeInstanceOf(SourceFaultOccurrence::class)
        ->and($this->pump->mail[0]->error->getMessage())->toBe('EIO');
});

it('rejects a defer budget below one', function () {
    expect(fn () => new GPIOResourceDriver($this->pump, 0))
        ->toThrow(GPIOException::class, 'defer_per_tick must be null or at least 1');
});
