<?php

use GeneralPurposeIO\Contracts\PWM\PWMException;
use GeneralPurposeIO\Contracts\PWM\PWMTransport;
use GeneralPurposeIO\NutsAndBolts\TransportCall;
use GeneralPurposeIO\PWM\PWMChannelGig;
use ScrapyardIO\Tests\Fixtures\ClosureJob;
use ScrapyardIO\Tests\Fixtures\FakePWMConnectionDriver;
use ScrapyardIO\Tests\Fixtures\FakePWMHandle;
use ScrapyardIO\Tests\Fixtures\FakePWMTransport;
use ScrapyardIO\Tests\Fixtures\ManualPool;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\WorkerPools\RemoteException;
use Voyager\Contracts\IOPools\WorkerPools\WorkerPool;
use Voyager\IOPools\EventLoop;
use ScrapyardIO\Tests\Fixtures\InlinePool;

/** A fake driver on $board with chip 0 connected, offloading to $pool on $loop. */
function offloadingPWM(Loop $loop, WorkerPool $pool, string $board = 'bench'): FakePWMConnectionDriver
{
    $driver = (new FakePWMConnectionDriver($board))
        ->resolvesLoopWith(fn (): Loop => $loop)
        ->resolvesPoolsWith(fn (?string $name): WorkerPool => $pool);
    $driver->connectTo(0)->register();

    return $driver;
}

beforeEach(function () {
    FakePWMTransport::$boards = [];
    FakePWMTransport::$log = [];
});

it('refuses to offload with no event loop bound', function () {
    $driver = new FakePWMConnectionDriver;
    $driver->connectTo(0)->register();

    expect(fn () => $driver->device(0, 0)->via()->setPeriod(20_000_000))
        ->toThrow(PWMException::class, 'via() needs an event loop');
});

it('refuses to offload from a closed channel', function () {
    $loop = testLoop();
    $servo = offloadingPWM($loop, new InlinePool($loop))->device(0, 0);
    $servo->close();

    expect(fn () => $servo->via())->toThrow(PWMException::class, 'PWM channel 0 is closed.');
});

it('refuses to offload from a channel no driver handed out', function () {
    expect(fn () => (new FakePWMTransport(0, 'bench', new FakePWMHandle(0)))->via())
        ->toThrow(PWMException::class, 'PWM channel 0 was not handed out by a connection driver, so it cannot be offloaded.');
});

it('resolves each offloaded call with what the blocking call returns', function () {
    $loop = testLoop();
    $via = offloadingPWM($loop, new InlinePool($loop))->device(0, 0)->via();

    expect($via->setPeriod(20_000_000)->wait())->toBe(20_000_000)
        ->and($via->setDutyCycle(1_500_000)->wait())->toBe(1_500_000)
        ->and($via->setEnable(true)->wait())->toBeTrue()
        ->and($via->setPolarity(false)->wait())->toBeFalse()
        ->and($via->getPeriod()->wait())->toBe(20_000_000)
        ->and($via->getDutyCycle()->wait())->toBe(1_500_000)
        ->and($via->getEnable()->wait())->toBeTrue()
        ->and($via->getPolarity()->wait())->toBeFalse()
        ->and($via->run(new ClosureJob(fn (PWMTransport $channel): int => $channel->channel()))->wait())->toBe(0)
        ->and(FakePWMTransport::$log)->toBe([
            'bench:0:0:period=20000000',
            'bench:0:0:duty_cycle=1500000',
            'bench:0:0:enable=true',
            'bench:0:0:polarity=false',
        ]);
});

it('rejects an offloaded call with what the blocking call throws, and the channel\'s next job still runs', function () {
    $loop = testLoop();
    $via = offloadingPWM($loop, new InlinePool($loop))->device(0, 0)->via();

    $via->setPeriod(20_000_000);
    $refused = $via->setDutyCycle(20_000_001);
    $next = $via->setDutyCycle(2_000_000);

    expect(fn () => $refused->wait())->toThrow(PWMException::class, 'Could not write PWM sysfs attribute: bench:0:0/duty_cycle')
        ->and($next->wait())->toBe(2_000_000);
});

it('turns a worker\'s RemoteException back into the scrapyard exception it was', function () {
    $loop = testLoop();
    $pool = new ManualPool($loop);
    $promise = offloadingPWM($loop, $pool)->device(0, 0)->via()->setPeriod(20_000_000);

    $pool->fail(new RemoteException(PWMException::class, 'PWM channel 0 is closed.', '#0 worker'));

    expect(fn () => $promise->wait())->toThrow(PWMException::class, 'PWM channel 0 is closed.');
});

it('runs one job at a time per channel, in call order', function () {
    $loop = testLoop();
    $pool = new ManualPool($loop);
    $servo = offloadingPWM($loop, $pool)->device(0, 0);

    $a = $servo->via()->setPeriod(1);
    $b = $servo->via()->setPeriod(2);
    $c = $servo->via()->setPeriod(3);

    expect($pool->running)->toHaveCount(1);

    $pool->finish();
    $loop->until(fn (): bool => $a->settled() && count($pool->running) === 1);
    $pool->finish();
    $loop->until(fn (): bool => $b->settled() && count($pool->running) === 1);
    $pool->finish();
    $loop->until(fn (): bool => $c->settled());

    expect(FakePWMTransport::$log)->toBe(['bench:0:0:period=1', 'bench:0:0:period=2', 'bench:0:0:period=3']);
});

it('runs different channels side by side', function () {
    $loop = testLoop();
    $pool = new ManualPool($loop);
    $driver = offloadingPWM($loop, $pool);

    $driver->device(0, 0)->via()->setPeriod(20_000_000);
    $driver->device(0, 1)->via()->setPeriod(40_000);

    expect($pool->running)->toHaveCount(2);
});

it('makes a blocking call wait for its own channel\'s offloaded jobs, never another channel\'s', function () {
    $loop = testLoop();
    $pool = new ManualPool($loop);
    $driver = offloadingPWM($loop, $pool);
    $servo = $driver->device(0, 0);
    $fan = $driver->device(0, 1);

    $servo->via()->setPeriod(20_000_000);
    $loop->at(0.01, fn () => $pool->finish());

    $fan->setPeriod(40_000);
    $servo->setDutyCycle(1_500_000);

    expect(FakePWMTransport::$log)->toBe(['bench:0:1:period=40000', 'bench:0:0:period=20000000', 'bench:0:0:duty_cycle=1500000']);
});

it('close() rejects the channel\'s queued jobs and lets its running one finish', function () {
    $loop = testLoop();
    $pool = new ManualPool($loop);
    $servo = offloadingPWM($loop, $pool)->device(0, 0);

    $a = $servo->via()->setPeriod(1);
    $b = $servo->via()->setPeriod(2);
    $c = $servo->via()->setPeriod(3);
    $loop->at(0.01, fn () => $pool->finish());

    $servo->close();

    expect($a->wait())->toBe(1)
        ->and(fn () => $b->wait())->toThrow(PWMException::class, 'PWM channel 0 is closed.')
        ->and(fn () => $c->wait())->toThrow(PWMException::class, 'PWM channel 0 is closed.')
        ->and($servo->closed())->toBeTrue()
        ->and(FakePWMTransport::$log)->toBe(['bench:0:0:period=1']);
});

it('disconnect() lets running jobs finish, rejects queued ones, then closes the chip', function () {
    $loop = testLoop();
    $pool = new ManualPool($loop);
    $driver = offloadingPWM($loop, $pool);
    $handle = $driver->connections->get(0);
    $servo = $driver->device(0, 0);
    $fan = $driver->device(0, 1);

    $a = $servo->via()->setPeriod(1);
    $x = $fan->via()->setPeriod(40_000);
    $b = $servo->via()->setPeriod(2);
    $loop->at(0.01, function () use ($pool) { $pool->finish(); $pool->finish(); });

    $driver->disconnect(0);

    expect($a->wait())->toBe(1)
        ->and($x->wait())->toBe(40_000)
        ->and(fn () => $b->wait())->toThrow(PWMException::class, 'PWM channel 0 is closed.')
        ->and($handle->closed)->toBeTrue()
        ->and($driver->connections->has(0))->toBeFalse();
});

it('leaves chip 11\'s queue alone when chip 1 disconnects', function () {
    $loop = testLoop();
    $pool = new ManualPool($loop);
    $driver = (new FakePWMConnectionDriver)
        ->resolvesLoopWith(fn (): Loop => $loop)
        ->resolvesPoolsWith(fn (?string $name): WorkerPool => $pool);
    $driver->connectTo(1)->register();
    $driver->connectTo(11)->register();

    $one = $driver->device(1, 0)->via()->setPeriod(1);
    $eleven = $driver->device(11, 0)->via()->setPeriod(2);
    $loop->at(0.01, fn () => $pool->finish());

    $driver->disconnect(1);
    $again = $driver->device(11, 0)->via()->setPeriod(3);

    expect($pool->running)->toHaveCount(1);        // chip 11's next job still waits behind its running one

    $pool->finish();
    $loop->until(fn (): bool => $eleven->settled() && count($pool->running) === 1);
    $pool->finish();

    expect($one->wait())->toBe(1)
        ->and($eleven->wait())->toBe(2)
        ->and($again->wait())->toBe(3)
        ->and(FakePWMTransport::$log)->toBe(['bench:1:0:period=1', 'bench:11:0:period=2', 'bench:11:0:period=3']);
});

it('ships a PWMChannelGig across a process pipe intact', function () {
    $gig = unserialize(serialize(new PWMChannelGig(FakePWMConnectionDriver::class, ['rig'], 0, 0, new TransportCall('setPeriod', [20_000_000]))));

    expect($gig->handle())->toBe(20_000_000)
        ->and(FakePWMTransport::$log)->toBe(['rig:0:0:period=20000000']);
});

it('builds the worker\'s driver from the caller\'s worker arguments', function () {
    $loop = testLoop();
    $servo = offloadingPWM($loop, new InlinePool($loop), 'rig')->device(0, 0);

    expect($servo->via()->setPeriod(20_000_000)->wait())->toBe(20_000_000)
        ->and(FakePWMTransport::$log)->toBe(['rig:0:0:period=20000000']);
});

it('keeps one driver per class and arguments, and its open chips, for the life of a worker', function () {
    (new PWMChannelGig(FakePWMConnectionDriver::class, ['rig'], 7, 0, new TransportCall('getPeriod')))->handle();
    (new PWMChannelGig(FakePWMConnectionDriver::class, ['rig'], 7, 1, new TransportCall('getPeriod')))->handle();
    (new PWMChannelGig(FakePWMConnectionDriver::class, ['lab'], 7, 0, new TransportCall('getPeriod')))->handle();

    $drivers = (new ReflectionProperty(PWMChannelGig::class, 'drivers'))->getValue();
    $on = fn (string $board): array => array_values(array_filter($drivers, fn (FakePWMConnectionDriver $driver): bool => $driver->board === $board));

    expect($on('rig'))->toHaveCount(1)
        ->and($on('rig')[0]->connections->has(7))->toBeTrue()
        ->and($on('lab'))->toHaveCount(1);
});


it('hands one transport to callers that opened the same channel while it was still coming up', function () {
    $loop = testLoop();
    $driver = offloadingPWM($loop, new InlinePool($loop));
    $up = false;
    $driver->opening = function () use ($loop, &$up): void {
        $loop->until(function () use (&$up): bool { return $up; });
    };

    $first = $loop->async(fn () => $driver->device(0, 0));
    $second = $loop->async(fn () => $driver->device(0, 0));
    $loop->at(0.01, function () use (&$up) { $up = true; });
    $loop->until(fn (): bool => $first->settled() && $second->settled());
    $driver->opening = null;

    expect($first->wait())->toBe($second->wait())
        ->and($driver->device(0, 0))->toBe($first->wait());
});
