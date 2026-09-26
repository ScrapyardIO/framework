<?php

use GeneralPurposeIO\Contracts\UART\UARTException;
use Voyager\IOPools\EventLoop;

/** Whether $loop has nothing left: no timer due, no resource registered. */
function loopIsEmpty(EventLoop $loop): bool
{
    $notebook = (new ReflectionProperty(EventLoop::class, 'notebook'))->getValue($loop);

    return is_null((new ReflectionMethod(EventLoop::class, 'nextDue'))->invoke($loop)) && ! $notebook->hasResources();
}

it('read() inside async() suspends while other fibers run', function () {
    $loop = new EventLoop;
    $port = uartPort($loop);
    $order = [];

    $reader = $loop->async(function () use ($port, &$order) {
        $order[] = 'waiting';
        $bytes = $port->read(8);
        $order[] = 'got '.array2bytes($bytes);
    });
    $loop->async(function () use (&$order) { $order[] = 'other fiber'; });
    $loop->at(0.02, fn () => fwrite($port->wire, 'abc'));
    $loop->until(fn (): bool => $reader->settled());

    expect($order)->toBe(['waiting', 'other fiber', 'got abc']);
});

it('read() on the main stack keeps timers firing', function () {
    $loop = new EventLoop;
    $port = uartPort($loop);
    $ticks = 0;
    $ticker = $loop->every(0.005, function () use (&$ticks) { $ticks++; }, 'ticker');
    $loop->at(0.04, fn () => fwrite($port->wire, 'x'));

    $bytes = $port->read(1);
    $ticker->cancel();

    expect($bytes)->toBe([0x78])
        ->and($ticks)->toBeGreaterThanOrEqual(5);
});

it('a read timeout on the loop returns nothing and leaves nothing registered', function () {
    $loop = new EventLoop;
    $port = uartPort($loop);

    $started = hrtime(true);
    $bytes = $port->read(4, 30);

    expect($bytes)->toBe([])
        ->and((hrtime(true) - $started) / 1e6)->toBeGreaterThanOrEqual(25.0)
        ->and(loopIsEmpty($loop))->toBeTrue();
});

it('readUntil() on the loop assembles a line from pieces', function () {
    $loop = new EventLoop;
    $port = uartPort($loop);
    $loop->at(0.01, fn () => fwrite($port->wire, '+RE'));
    $loop->at(0.02, fn () => fwrite($port->wire, "ADY\r\n"));

    expect($port->readUntil("\r\n", 500))->toBe("+READY\r\n");
});

it('write() on the loop waits for room while the loop turns', function () {
    $loop = new EventLoop;
    $port = uartPort($loop);
    $port->room = 0;
    $ticks = 0;
    $ticker = $loop->every(0.005, function () use (&$ticks) { $ticks++; }, 'ticker');
    $loop->at(0.03, function () use ($port) { $port->room = PHP_INT_MAX; });

    $wrote = $port->write("AT\r\n");
    $ticker->cancel();

    expect($wrote)->toBe(4)
        ->and($ticks)->toBeGreaterThanOrEqual(3)
        ->and($port->sent())->toBe("AT\r\n");
});

it('a write timeout on the loop throws and leaves no timer behind', function () {
    $loop = new EventLoop;
    $port = uartPort($loop);
    $port->room = 0;

    expect(fn () => $port->write('x', 30))
        ->toThrow(UARTException::class, 'UART port bench took no more bytes before the timeout: 0 of 1 went out.')
        ->and(loopIsEmpty($loop))->toBeTrue();
});

it('close() wakes a waiting read with an exception', function () {
    $loop = new EventLoop;
    $port = uartPort($loop);

    $reader = $loop->async(fn () => $port->read(4));
    $loop->at(0.02, fn () => $port->close());
    $loop->until(fn (): bool => $reader->settled());
    $loop->run();       // the one no-op turn close() keeps due for its waiter

    expect(fn () => $reader->wait())->toThrow(UARTException::class, 'UART port bench is closed.')
        ->and(loopIsEmpty($loop))->toBeTrue();
});

it('a sampled port is sampled on its timer while a read waits', function () {
    $loop = new EventLoop;
    $port = uartPort($loop);
    $port->sample_every = 0.005;
    $loop->at(0.02, fn () => fwrite($port->wire, 'ok'));

    expect($port->read(2, 500))->toBe(bytes2array('ok'))
        ->and(loopIsEmpty($loop))->toBeTrue();
});

it('two fibers writing to one port each keep their bytes together', function () {
    $loop = new EventLoop;
    $port = uartPort($loop);
    $port->room = 256;
    $refill = $loop->every(0.005, function () use ($port) { $port->room += 256; }, 'refill');

    $a = $loop->async(fn () => $port->write(str_repeat('a', 600)));
    $b = $loop->async(fn () => $port->write(str_repeat('b', 600)));
    $loop->until(fn (): bool => $a->settled() && $b->settled());
    $refill->cancel();

    expect($port->sent())->toBe(str_repeat('a', 600).str_repeat('b', 600));
});

it('close() from a mail listener still wakes a waiting read with an exception', function () {
    $listener = new class implements Voyager\Contracts\IOPools\Receivable {
        public ?ScrapyardIO\Tests\Fixtures\FakeUARTTransport $port = null;

        public function handOff(Voyager\Contracts\IOPools\MailCollection $mail): void
        {
            $this->port?->close();
        }
    };
    $loop = new EventLoop(null, 16, $listener);
    $port = $listener->port = uartPort($loop);

    $port->watch();
    $reader = $loop->async(fn () => $port->readUntil("\n"));
    $loop->at(0.01, fn () => fwrite($port->wire, 'x'));
    $loop->run();

    expect(fn () => $reader->wait())->toThrow(UARTException::class, 'UART port bench is closed.');
});

it('a port that fails under a waiting read hands the waiter the failure and leaves the loop', function () {
    $loop = new EventLoop;
    $port = uartPort($loop);
    $port->sample_every = 0.005;

    $reader = $loop->async(fn () => $port->read(4));
    $loop->at(0.02, function () use ($port) { $port->failing = true; });
    $loop->until(fn (): bool => $reader->settled());

    expect(fn () => $reader->wait())->toThrow(UARTException::class, 'Could not read from UART port bench.')
        ->and(loopIsEmpty($loop))->toBeTrue();
});
