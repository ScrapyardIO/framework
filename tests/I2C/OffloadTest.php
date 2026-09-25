<?php

use GeneralPurposeIO\Contracts\I2C\I2CException;
use GeneralPurposeIO\I2C\BusGig;
use GeneralPurposeIO\NutsAndBolts\TransportCall;
use ScrapyardIO\Tests\Fixtures\EchoAddressJob;
use ScrapyardIO\Tests\Fixtures\FakeBridgeI2CConnectionDriver;
use ScrapyardIO\Tests\Fixtures\FakeI2CConnectionDriver;
use ScrapyardIO\Tests\Fixtures\FakeI2CHandle;
use ScrapyardIO\Tests\Fixtures\FakeI2CTransport;
use ScrapyardIO\Tests\Fixtures\ManualTarget;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\RemoteException;
use Voyager\Contracts\IOPools\WorkTarget;
use Voyager\IOPools\EventLoop;
use Voyager\IOPools\WorkTargets\DeferTarget;
use Voyager\IOPools\WorkTargets\QueueTarget;
use Voyager\IOPools\WorkTargets\SyncTarget;

/** A fake driver with bus 1 connected, offloading to $target on $loop. */
function offloadingI2C(Loop $loop, WorkTarget $target): FakeI2CConnectionDriver
{
    $driver = (new FakeI2CConnectionDriver)
        ->resolvesLoopWith(fn (): Loop => $loop)
        ->resolvesTargetsWith(fn (?string $name): WorkTarget => $target);
    $driver->connectTo(1)->register();

    return $driver;
}

beforeEach(function () {
    FakeI2CTransport::$log = [];
});

it('refuses to offload with no event loop bound', function () {
    $driver = new FakeI2CConnectionDriver;
    $driver->connectTo(1)->register();

    expect(fn () => $driver->device(1, 0x3C)->via()->write([0x00]))->toThrow(I2CException::class, 'via() needs an event loop');
});

it('refuses to offload from a closed slave', function () {
    $loop = new EventLoop;
    $slave = offloadingI2C($loop, new SyncTarget($loop))->device(1, 0x3C);
    $slave->close();

    expect(fn () => $slave->via())->toThrow(I2CException::class, 'I2C slave 0x3C is closed.');
});

it('refuses to offload from a slave no driver handed out', function () {
    expect(fn () => (new FakeI2CTransport(0x3C, new FakeI2CHandle(1)))->via())->toThrow(I2CException::class, 'was not handed out by a connection driver');
});

it('resolves each offloaded call with what the blocking call returns', function () {
    $loop = new EventLoop;
    $slave = offloadingI2C($loop, new SyncTarget($loop))->device(1, 0x3C);

    expect($slave->via()->write([0x00, 0xAF])->wait())->toBe(2)
        ->and($slave->via()->read(3)->wait())->toBe([0, 0, 0])
        ->and($slave->via()->writeRead([0x00], 2)->wait())->toBe([0, 0])
        ->and($slave->via()->bulkWrite([[0x00, 0xAE], [0x40, 0x01, 0x02]])->wait())->toBe([2, 3])
        ->and(FakeI2CTransport::$log)->toBe(['1:60:00af', '1:60:00', '1:60:00ae', '1:60:400102']);
});

it('rejects an offloaded call with what the blocking call throws', function () {
    $loop = new EventLoop;
    $slave = offloadingI2C($loop, new SyncTarget($loop))->device(1, 0x3C);

    expect(fn () => $slave->via()->write(str_repeat("\x00", 8193))->wait())
        ->toThrow(I2CException::class, '8193 bytes is longer than the 8192-byte I2C message limit.');
});

it('turns a worker\'s RemoteException back into the scrapyard exception it was', function () {
    $loop = new EventLoop;
    $target = new ManualTarget($loop);
    $promise = offloadingI2C($loop, $target)->device(1, 0x3C)->via()->write([0x00]);

    $target->fail(new RemoteException(I2CException::class, 'I2C slave 0x3C is closed.', '#0 worker'));

    expect(fn () => $promise->wait())->toThrow(I2CException::class, 'I2C slave 0x3C is closed.');
});

it('leaves an exception from outside scrapyard as the RemoteException it arrived as', function () {
    $loop = new EventLoop;
    $target = new ManualTarget($loop);
    $promise = offloadingI2C($loop, $target)->device(1, 0x3C)->via()->write([0x00]);

    $target->fail(new RemoteException(RuntimeException::class, 'disk on fire', '#0 worker'));

    expect(fn () => $promise->wait())->toThrow(RemoteException::class, '[RuntimeException] disk on fire');
});

it('runs one job at a time per slave, in call order', function () {
    $loop = new EventLoop;
    $target = new ManualTarget($loop);
    $slave = offloadingI2C($loop, $target)->device(1, 0x3C);

    $a = $slave->via()->write([0x01]);
    $b = $slave->via()->write([0x02]);
    $c = $slave->via()->write([0x03]);

    expect($target->running)->toHaveCount(1);

    $target->finish();
    $loop->until(fn (): bool => $a->settled() && count($target->running) === 1);
    $target->finish();
    $loop->until(fn (): bool => $b->settled() && count($target->running) === 1);
    $target->finish();
    $loop->until(fn (): bool => $c->settled());

    expect(FakeI2CTransport::$log)->toBe(['1:60:01', '1:60:02', '1:60:03']);
});

it('runs different slaves side by side', function () {
    $loop = new EventLoop;
    $target = new ManualTarget($loop);
    $driver = offloadingI2C($loop, $target);

    $driver->device(1, 0x3C)->via()->write([0x01]);
    $driver->device(1, 0x21)->via()->write([0x02]);

    expect($target->running)->toHaveCount(2);
});

it('makes a blocking call wait for its own slave\'s offloaded jobs, never another slave\'s', function () {
    $loop = new EventLoop;
    $target = new ManualTarget($loop);
    $driver = offloadingI2C($loop, $target);
    $panel = $driver->device(1, 0x3C);
    $fan = $driver->device(1, 0x21);

    $panel->via()->write([0x01]);
    $loop->at(0.01, fn () => $target->finish());

    $fan->write([0x09]);
    $panel->write([0x02]);

    expect(FakeI2CTransport::$log)->toBe(['1:33:09', '1:60:01', '1:60:02']);
});

it('close() rejects the slave\'s queued jobs and lets its running one finish', function () {
    $loop = new EventLoop;
    $target = new ManualTarget($loop);
    $panel = offloadingI2C($loop, $target)->device(1, 0x3C);

    $a = $panel->via()->write([0x01]);
    $b = $panel->via()->write([0x02]);
    $c = $panel->via()->write([0x03]);
    $loop->at(0.01, fn () => $target->finish());

    $panel->close();

    expect($a->wait())->toBe(1)
        ->and(fn () => $b->wait())->toThrow(I2CException::class, 'I2C slave 0x3C is closed.')
        ->and(fn () => $c->wait())->toThrow(I2CException::class, 'I2C slave 0x3C is closed.')
        ->and($panel->closed())->toBeTrue()
        ->and(FakeI2CTransport::$log)->toBe(['1:60:01']);
});

it('close() on one slave leaves the other slaves\' jobs alone when they share a queue', function () {
    $loop = new EventLoop;
    $target = new ManualTarget($loop);
    $driver = (new FakeBridgeI2CConnectionDriver)
        ->resolvesLoopWith(fn (): Loop => $loop)
        ->resolvesTargetsWith(fn (?string $name): WorkTarget => $target);
    $driver->connectTo(1)->register();
    $panel = $driver->device(1, 0x3C);
    $fan = $driver->device(1, 0x21);

    $a = $fan->via()->write([0x01]);
    $b = $panel->via()->write([0x02]);
    $c = $fan->via()->write([0x03]);

    $panel->close();

    $target->finish();
    $loop->until(fn (): bool => $a->settled() && count($target->running) === 1);
    $target->finish();

    expect($a->wait())->toBe(1)
        ->and(fn () => $b->wait())->toThrow(I2CException::class, 'I2C slave 0x3C is closed.')
        ->and($c->wait())->toBe(1)
        ->and(FakeI2CTransport::$log)->toBe(['1:33:01', '1:33:03']);
});

it('disconnect() lets running jobs finish, rejects queued ones, then closes the bus', function () {
    $loop = new EventLoop;
    $target = new ManualTarget($loop);
    $driver = offloadingI2C($loop, $target);
    $handle = $driver->connections->get(1);
    $panel = $driver->device(1, 0x3C);
    $fan = $driver->device(1, 0x21);

    $a = $panel->via()->write([0x01]);
    $x = $fan->via()->write([0x09]);
    $b = $panel->via()->write([0x02]);
    $loop->at(0.01, function () use ($target) { $target->finish(); $target->finish(); });

    $driver->disconnect(1);

    expect($a->wait())->toBe(1)
        ->and($x->wait())->toBe(1)
        ->and(fn () => $b->wait())->toThrow(I2CException::class, 'I2C slave 0x3C is closed.')
        ->and($handle->closed)->toBeTrue()
        ->and($driver->connections->has(1))->toBeFalse();
});

it('runs a chip driver\'s own BusJob against the slave', function () {
    $loop = new EventLoop;
    $slave = offloadingI2C($loop, new SyncTarget($loop))->device(1, 0x3C);

    expect($slave->via()->run(new EchoAddressJob)->wait())->toBe(0x3C);
});

it('ships a BusGig across a process pipe intact', function () {
    $gig = unserialize(serialize(new BusGig(FakeI2CConnectionDriver::class, 1, 0x3C, new TransportCall('write', [[0x00, 0xAF]]))));

    expect($gig->handle())->toBe(2)
        ->and(FakeI2CTransport::$log)->toBe(['1:60:00af']);
});

it('keeps one driver per class, and its open buses, for the life of a worker', function () {
    (new BusGig(FakeI2CConnectionDriver::class, 7, 0x3C, new EchoAddressJob))->handle();
    (new BusGig(FakeI2CConnectionDriver::class, 7, 0x21, new EchoAddressJob))->handle();

    $drivers = (new ReflectionProperty(BusGig::class, 'drivers'))->getValue();

    expect($drivers[FakeI2CConnectionDriver::class]->connections->has(7))->toBeTrue()
        ->and($drivers[FakeI2CConnectionDriver::class]->connections->count())->toBeGreaterThanOrEqual(1);
});

it('makes a blocking call wait for the jobs queued before it, not for a producer that keeps re-queueing', function () {
    $loop = new EventLoop;
    $slave = offloadingI2C($loop, new DeferTarget($loop))->device(1, 0x3C);
    $produced = 0;
    $produce = function () use (&$produce, $slave, &$produced) {
        if ($produced++ < 200) {
            $slave->via()->write([0x40])->then(fn () => $produce());
        }
    };

    $produce();
    $slave->write([0x99]);

    expect(array_search('1:60:99', FakeI2CTransport::$log, true))->toBe(1);
});

it('close() refuses new offloads while it waits for the running job', function () {
    $loop = new EventLoop;
    $target = new ManualTarget($loop);
    $panel = offloadingI2C($loop, $target)->device(1, 0x3C);
    $refused = null;

    $a = $panel->via()->write([0x01]);
    $loop->at(0.01, function () use ($panel, $target, &$refused) {
        try {
            $panel->via()->write([0x02]);
        } catch (I2CException $e) {
            $refused = $e->getMessage();
        }

        $target->finish();
    });

    $panel->close();

    expect($refused)->toBe('I2C slave 0x3C is closed.')
        ->and($a->wait())->toBe(1)
        ->and($target->running)->toBe([])
        ->and(FakeI2CTransport::$log)->toBe(['1:60:01']);
});

it('refuses a via() handle taken before close()', function () {
    $loop = new EventLoop;
    $slave = offloadingI2C($loop, new SyncTarget($loop))->device(1, 0x3C);
    $offloaded = $slave->via();

    $slave->close();

    expect(fn () => $offloaded->write([0x01]))->toThrow(I2CException::class, 'I2C slave 0x3C is closed.')
        ->and(FakeI2CTransport::$log)->toBe([]);
});

it('keeps the worker\'s RemoteException, trace and all, behind the localized exception', function () {
    $loop = new EventLoop;
    $target = new ManualTarget($loop);
    $promise = offloadingI2C($loop, $target)->device(1, 0x3C)->via()->write([0x00]);

    $target->fail(new RemoteException(I2CException::class, 'I2C slave 0x3C is closed.', '#0 worker'));

    try {
        $promise->wait();
    } catch (I2CException $e) {
        $caught = $e;
    }

    expect($caught->getPrevious())->toBeInstanceOf(RemoteException::class)
        ->and($caught->getPrevious()->remote_trace)->toBe('#0 worker');
});

it('refuses a work target that hands back a queued job instead of the result', function () {
    $loop = new EventLoop;
    $queue = (new ReflectionClass(QueueTarget::class))->newInstanceWithoutConstructor();
    $slave = offloadingI2C($loop, $queue)->device(1, 0x3C);

    expect(fn () => $slave->via('queue')->write([0x00])->wait())
        ->toThrow(I2CException::class, 'The [queue] work target hands back a queued job, not the call\'s result');
});
