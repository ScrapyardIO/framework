<?php

use ScrapyardIO\Tests\Fixtures\FakeDigitalIOConnectionDriver;
use ScrapyardIO\Tests\Fixtures\SocketDigitalInputTransport;
use Voyager\Contracts\IOPools\Loop;
use Voyager\IOPools\EventLoop;

function benchDriver(?Loop $loop = null): FakeDigitalIOConnectionDriver
{
    $driver = new FakeDigitalIOConnectionDriver;

    if (! is_null($loop)) {
        $driver->resolvesLoopWith(fn (): Loop => $loop);
    }

    $driver->connectTo('bench')->register();

    return $driver;
}

it('hands every input pin its device and the loop resolver', function () {
    $loop = testLoop();
    $pin = benchDriver($loop)->input('bench', 3);

    $pin->watch();
    $pin->unwatch();

    expect($pin)->toBeInstanceOf(SocketDigitalInputTransport::class)
        ->and($pin->device())->toBe('bench')
        ->and($pin->name())->toBe('gpio.edge.bench.3');
});

it('returns null for a device it never connected', function () {
    $driver = benchDriver();

    expect($driver->input('elsewhere', 3))->toBeNull()
        ->and($driver->output('elsewhere', 3))->toBeNull();
});

it('disconnect() closes the device pins, then the device', function () {
    $driver = benchDriver();
    $in = $driver->input('bench', 3);
    $out = $driver->output('bench', 4);

    $driver->disconnect('bench');

    expect($in->closed())->toBeTrue()
        ->and($out->closed())->toBeTrue()
        ->and($out->released)->toBeTrue()
        ->and($driver->closed_connections)->toBe(['handle:bench'])
        ->and($driver->connections->has('bench'))->toBeFalse()
        ->and($driver->input('bench', 3))->toBeNull();

    $driver->connectTo('bench')->register();

    expect($driver->input('bench', 3)->closed())->toBeFalse();
});

it('closing one pin leaves the device and its other pins open', function () {
    $driver = benchDriver();
    $a = $driver->input('bench', 3);
    $b = $driver->input('bench', 5);

    $a->close();

    expect($b->closed())->toBeFalse()
        ->and($driver->closed_connections)->toBe([]);
});

it('a closed pin is requested fresh on the next input() or output()', function () {
    $driver = benchDriver();
    $in = $driver->input('bench', 3);
    $out = $driver->output('bench', 4);

    expect($driver->input('bench', 3))->toBe($in);

    $in->close();
    $out->close();

    $again_in = $driver->input('bench', 3);
    $again_out = $driver->output('bench', 4);

    expect($again_in)->not->toBe($in)
        ->and($again_in->closed())->toBeFalse()
        ->and($again_out)->not->toBe($out)
        ->and($again_out->closed())->toBeFalse();
});
