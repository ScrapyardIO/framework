<?php

use GeneralPurposeIO\Contracts\SPI\SPIException;
use GeneralPurposeIO\Contracts\SPI\SPIMode;
use GeneralPurposeIO\NutsAndBolts\TransportCall;
use GeneralPurposeIO\SPI\SPIBusGig;
use GeneralPurposeIO\SPI\SPIBusSettings;
use GeneralPurposeIO\SPI\SPIConnectionDriver;
use ScrapyardIO\Tests\Fixtures\ClosureJob;
use ScrapyardIO\Tests\Fixtures\EchoChipSelectJob;
use ScrapyardIO\Tests\Fixtures\FakeBridgeSPIConnectionDriver;
use ScrapyardIO\Tests\Fixtures\FakeSPIConnectionDriver;
use ScrapyardIO\Tests\Fixtures\FakeSPIHandle;
use ScrapyardIO\Tests\Fixtures\FakeSPITransport;
use ScrapyardIO\Tests\Fixtures\ManualPool;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\WorkerPools\RemoteException;
use Voyager\Contracts\IOPools\WorkerPools\WorkerPool;
use Voyager\IOPools\EventLoop;
use ScrapyardIO\Tests\Fixtures\DeferredPool;
use ScrapyardIO\Tests\Fixtures\InlinePool;

/** $driver (a plain fake unless given) with $loop and $pool (sync unless given) wired, and bus $bus connected through its factory. */
function offloadingSPI(Loop $loop, ?WorkerPool $pool = null, ?SPIConnectionDriver $driver = null, int $bus = 1): SPIConnectionDriver
{
    $driver ??= new FakeSPIConnectionDriver;
    $driver->resolvesLoopWith(fn (): Loop => $loop)
        ->resolvesPoolsWith(fn (?string $name): WorkerPool => $pool ?? new InlinePool($loop));
    $driver->connectTo($bus)->register();

    return $driver;
}

beforeEach(function () {
    FakeSPITransport::$log = [];
});

it('refuses to offload with no event loop bound', function () {
    $driver = new FakeSPIConnectionDriver;
    $driver->connectTo(1)->register();

    expect(fn () => $driver->device(1, 0)->via()->write([0x00]))->toThrow(SPIException::class, 'via() needs an event loop');
});

it('refuses to offload from a closed slave, and from one no driver handed out', function () {
    $slave = offloadingSPI(testLoop())->device(1, 0);
    $slave->close();

    expect(fn () => $slave->via())->toThrow(SPIException::class, 'SPI chip select 0 is closed.')
        ->and(fn () => (new FakeSPITransport(0, new FakeSPIHandle(1)))->via())->toThrow(SPIException::class, 'was not handed out by a connection driver');
});

it('resolves each offloaded call with what the blocking call returns', function () {
    $slave = offloadingSPI(testLoop())->device(1, 0);

    expect($slave->via()->write([0x00, 0xAF])->wait())->toBe(2)
        ->and($slave->via()->read(3)->wait())->toBe([0, 0, 0])
        ->and($slave->via()->transfer([0x01, 0x02])->wait())->toBe([0x01, 0x02])
        ->and($slave->via()->writeRead([0x0B], 2)->wait())->toBe([0, 0])
        ->and($slave->via()->run(new EchoChipSelectJob)->wait())->toBe(0)
        ->and(FakeSPITransport::$log)->toBe(['1:0:write:00af', '1:0:read:3', '1:0:transfer:0102', '1:0:writeRead:0b:2']);
});

it('turns a worker\'s RemoteException back into the SPIException it was, and keeps the worker\'s behind it', function () {
    $loop = testLoop();
    $pool = new ManualPool($loop);
    $promise = offloadingSPI($loop, $pool)->device(1, 0)->via()->write([0x00]);
    $caught = null;

    $pool->fail(new RemoteException(SPIException::class, 'SPI chip select 0 is closed.', '#0 worker'));

    try {
        $promise->wait();
    } catch (SPIException $e) {
        $caught = $e;
    }

    expect($caught?->getMessage())->toBe('SPI chip select 0 is closed.')
        ->and($caught?->getPrevious())->toBeInstanceOf(RemoteException::class)
        ->and($caught?->getPrevious()->remote_trace)->toBe('#0 worker');
});

it('runs one job at a time per bus, whichever slave queued it, in call order', function () {
    $loop = testLoop();
    $pool = new ManualPool($loop);
    $driver = offloadingSPI($loop, $pool);

    $a = $driver->device(1, 0)->via()->write([0x01]);
    $b = $driver->device(1, 1)->via()->write([0x02]);
    $c = $driver->device(1, 0)->via()->write([0x03]);

    expect($pool->running)->toHaveCount(1);

    $pool->finish();
    $loop->until(fn (): bool => $a->settled() && count($pool->running) === 1);
    $pool->finish();
    $loop->until(fn (): bool => $b->settled() && count($pool->running) === 1);
    $pool->finish();
    $loop->until(fn (): bool => $c->settled());

    expect(FakeSPITransport::$log)->toBe(['1:0:write:01', '1:1:write:02', '1:0:write:03']);
});

it('makes a blocking call wait for the jobs queued on its bus before it, from any slave', function () {
    $loop = testLoop();
    $pool = new ManualPool($loop);
    $driver = offloadingSPI($loop, $pool);

    $driver->device(1, 0)->via()->write([0x01]);
    $loop->at(0.01, fn () => $pool->finish());

    $driver->device(1, 1)->write([0x09]);

    expect(FakeSPITransport::$log)->toBe(['1:0:write:01', '1:1:write:09']);
});

it('makes a blocking call wait for the jobs queued before it, not for a producer that keeps re-queueing', function () {
    $loop = testLoop();
    $slave = offloadingSPI($loop, new DeferredPool($loop))->device(1, 0);
    $produced = 0;
    $produce = function () use (&$produce, $slave, &$produced) {
        if ($produced++ < 200) {
            $slave->via()->write([0x40])->then(fn () => $produce());
        }
    };

    $produce();
    $slave->write([0x99]);

    expect(array_search('1:0:write:99', FakeSPITransport::$log, true))->toBe(1);
});

it('close() rejects the slave\'s queued jobs, lets its running one finish, and leaves other slaves\' jobs queued', function () {
    $loop = testLoop();
    $pool = new ManualPool($loop);
    $driver = offloadingSPI($loop, $pool);
    $flash = $driver->device(1, 0);
    $sensor = $driver->device(1, 1);

    $a = $flash->via()->write([0x01]);
    $b = $sensor->via()->write([0x02]);
    $c = $flash->via()->write([0x03]);
    $loop->at(0.01, fn () => $pool->finish());

    $flash->close();
    $loop->until(fn (): bool => count($pool->running) === 1);
    $pool->finish();

    expect($a->wait())->toBe(1)
        ->and($b->wait())->toBe(1)
        ->and(fn () => $c->wait())->toThrow(SPIException::class, 'SPI chip select 0 is closed.')
        ->and($flash->closed())->toBeTrue()
        ->and(FakeSPITransport::$log)->toBe(['1:0:write:01', '1:0:release', '1:1:write:02']);
});

it('close() refuses new offloads while it waits for the running job, and so does a via() handle taken before it', function () {
    $loop = testLoop();
    $pool = new ManualPool($loop);
    $flash = offloadingSPI($loop, $pool)->device(1, 0);
    $early = $flash->via();
    $refused = null;

    $a = $flash->via()->write([0x01]);
    $loop->at(0.01, function () use ($flash, $pool, &$refused) {
        try {
            $flash->via()->write([0x02]);
        } catch (SPIException $e) {
            $refused = $e->getMessage();
        }

        $pool->finish();
    });

    $flash->close();

    expect($refused)->toBe('SPI chip select 0 is closed.')
        ->and($a->wait())->toBe(1)
        ->and(fn () => $early->write([0x03]))->toThrow(SPIException::class, 'SPI chip select 0 is closed.')
        ->and($pool->running)->toBe([]);
});

it('disconnect() lets running jobs finish, rejects queued ones, closes the bus and forgets its settings', function () {
    $loop = testLoop();
    $pool = new ManualPool($loop);
    $driver = offloadingSPI($loop, $pool);
    $handle = $driver->connections->get(1);

    $a = $driver->device(1, 0)->via()->write([0x01]);
    $b = $driver->device(1, 1)->via()->write([0x02]);
    $c = $driver->device(1, 0)->via()->write([0x03]);
    $loop->at(0.01, fn () => $pool->finish());
    $loop->at(0.03, fn () => $pool->finish());

    $driver->disconnect(1);

    expect($a->wait())->toBe(1)
        ->and($b->wait())->toBe(1)
        ->and(fn () => $c->wait())->toThrow(SPIException::class, 'SPI chip select 0 is closed.')
        ->and($handle->closed)->toBeTrue()
        ->and($driver->settingsOf(1))->toBeNull()
        ->and($driver->connections->has(1))->toBeFalse();
});

it('leaves bus 11 alone when bus 1 disconnects', function () {
    $loop = testLoop();
    $pool = new ManualPool($loop);
    $driver = offloadingSPI($loop, $pool);
    $driver->connectTo(11)->register();

    $job = $driver->device(11, 0)->via()->write([0x0B]);
    $driver->disconnect(1);
    $pool->finish();

    expect($job->wait())->toBe(1)
        ->and($driver->settingsOf(11))->toEqual(new SPIBusSettings)
        ->and($driver->connections->has(11))->toBeTrue();
});


it('refuses to offload from a bus registered without its factory, which a worker could not open the same way', function () {
    $loop = testLoop();
    $driver = (new FakeSPIConnectionDriver)
        ->resolvesLoopWith(fn (): Loop => $loop)
        ->resolvesPoolsWith(fn (?string $name): WorkerPool => new InlinePool($loop));
    $driver->register(3, new FakeSPIHandle(3));

    expect(fn () => $driver->device(3, 0)->via()->write([0x00])->wait())
        ->toThrow(SPIException::class, 'SPI device 3 was registered without its connection factory');
});

it('ships an SPIBusGig across a process pipe with the bus settings and the slave\'s clock', function () {
    $gig = unserialize(serialize(new SPIBusGig(FakeSPIConnectionDriver::class, 7, 0, new SPIBusSettings(SPIMode::MODE_3, 2_000_000), 4_000_000, new TransportCall('write', [[0xAF]]))));

    expect($gig->settings)->toEqual(new SPIBusSettings(SPIMode::MODE_3, 2_000_000))
        ->and($gig->hz)->toBe(4_000_000)
        ->and($gig->handle())->toBe(1)
        ->and(FakeSPITransport::$log)->toBe(['7:0:speed:4000000', '7:0:write:af']);
});

it('keeps a worker\'s bus open between gigs, and reconnects it when the settings changed', function () {
    $mode3 = new SPIBusSettings(SPIMode::MODE_3, 1_000_000);

    (new SPIBusGig(FakeSPIConnectionDriver::class, 8, 0, new SPIBusSettings(SPIMode::MODE_0, 1_000_000), null, new EchoChipSelectJob))->handle();
    $worker = (new ReflectionProperty(SPIBusGig::class, 'drivers'))->getValue()[FakeSPIConnectionDriver::class];
    $first = $worker->connections->get(8);

    (new SPIBusGig(FakeSPIConnectionDriver::class, 8, 0, new SPIBusSettings(SPIMode::MODE_0, 1_000_000), null, new EchoChipSelectJob))->handle();
    $kept = $worker->connections->get(8);

    (new SPIBusGig(FakeSPIConnectionDriver::class, 8, 0, $mode3, null, new EchoChipSelectJob))->handle();

    expect($kept)->toBe($first)
        ->and($first->closed)->toBeTrue()
        ->and($worker->connections->get(8))->not->toBe($first)
        ->and($worker->settingsOf(8))->toEqual($mode3);
});

it('runs the worker\'s slave at the clock the queuing slave has, and back at the connection\'s', function () {
    $loop = testLoop();
    $driver = offloadingSPI($loop, bus: 21);
    $slave = $driver->device(21, 0);

    $slave->speed(2_000_000);
    $slave->via()->write([0x01])->wait();
    $slave->via()->write([0x02])->wait();
    $slave->close();
    $driver->device(21, 0)->via()->write([0x03])->wait();

    expect(FakeSPITransport::$log)->toBe([
        '21:0:speed:2000000',       // this process's slave
        '21:0:speed:2000000',       // the worker's, once
        '21:0:write:01',
        '21:0:write:02',
        '21:0:release',
        '21:0:speed:800000',        // a fresh slave runs at the connection's clock, and so does the worker's again
        '21:0:write:03',
    ]);
});

it('lets a job queued during a select() start only once chip select is up', function () {
    $driver = offloadingSPI(testLoop(), driver: new FakeBridgeSPIConnectionDriver);
    $sensor = $driver->device(1, 1);
    $queued = null;

    $driver->device(1, 0)->select(function (FakeSPITransport $flash) use ($sensor, &$queued) {
        $queued = $sensor->via()->write([0x02]);
        $flash->write([0x01]);
    });

    expect($queued->wait())->toBe(1)
        ->and(FakeSPITransport::$log)->toBe(['1:0:select', '1:0:write:01', '1:0:deselect', '1:1:write:02']);
});

it('makes a select() wait for a job that started while it waited for the earlier ones', function () {
    $loop = testLoop();
    $driver = offloadingSPI($loop, driver: new FakeBridgeSPIConnectionDriver);
    $sensor = $driver->device(1, 1);
    $gates = new ArrayObject(['j1' => false, 'j2' => false]);
    $job = fn (string $gate, int $first, int $second): ClosureJob => new ClosureJob(function (FakeSPITransport $bus) use ($loop, $gates, $gate, $first, $second): int {
        $bus->write([$first]);
        $loop->until(fn (): bool => $gates[$gate]);

        return $bus->write([$second]);
    });

    $sensor->via()->run($job('j1', 0x01, 0x02));
    $loop->at(0.005, fn () => $sensor->via()->run($job('j2', 0x03, 0x04)));
    $loop->at(0.01, fn () => $gates['j1'] = true);
    $loop->at(0.02, fn () => $gates['j2'] = true);

    $driver->device(1, 0)->select(fn (FakeSPITransport $flash) => $flash->write([0x09]));

    expect(FakeSPITransport::$log)->toBe(['1:1:write:01', '1:1:write:02', '1:1:write:03', '1:1:write:04', '1:0:select', '1:0:write:09', '1:0:deselect']);
});

it('makes a blocking call wait while a job\'s select() holds the bus, even on that same slave, instead of joining the selection', function () {
    $loop = testLoop();
    $driver = offloadingSPI($loop, driver: new FakeBridgeSPIConnectionDriver);
    $sensor = $driver->device(1, 1);
    $gates = new ArrayObject(['open' => false]);

    $sensor->via()->run(new ClosureJob(fn (FakeSPITransport $bus) => $bus->select(function (FakeSPITransport $held) use ($loop, $gates): void {
        $held->write([0x01]);
        $loop->until(fn (): bool => $gates['open']);
        $held->write([0x02]);
    })));
    $loop->at(0.01, fn () => $gates['open'] = true);

    $sensor->write([0x09]);

    expect(FakeSPITransport::$log)->toBe(['1:1:select', '1:1:write:01', '1:1:write:02', '1:1:deselect', '1:1:write:09']);
});
