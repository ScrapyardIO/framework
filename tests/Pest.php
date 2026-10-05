<?php

use ScrapyardIO\Tests\Fixtures\FakeUARTConnectionDriver;
use ScrapyardIO\Tests\Fixtures\FakeUARTTransport;
use ScrapyardIO\Tests\Fixtures\SampledDigitalInputTransport;
use ScrapyardIO\Tests\Fixtures\SocketDigitalInputTransport;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\MailHandler;
use Voyager\IOPools\EventLoop;
use Voyager\IOPools\LoopWaiter;
use Voyager\IOPools\PromiseEngines\GuzzlePromiseEngine;
use Voyager\IOPools\ResourceRegistry;
use Voyager\IOPools\Waiter\StreamSelectWaiterBackend;

pest()->in('Core', 'Digital', 'I2C', 'SPI', 'NutsAndBolts', 'PWM', 'UART');

/** A loop on the select backend, polling at most every $pace_ms, handing its mail to $mail when given. */
function testLoop(?MailHandler $mail = null, int $pace_ms = 16): EventLoop
{
    $registry = new ResourceRegistry;

    return new EventLoop($registry, new LoopWaiter($registry, new StreamSelectWaiterBackend, $pace_ms * 1_000_000), new GuzzlePromiseEngine, $mail);
}

/** A socket-backed pin on device "bench", resolving $loop (or no loop). */
function socketPin(?Loop $loop, int $pin = 17): SocketDigitalInputTransport
{
    $transport = (new SocketDigitalInputTransport($pin))->boundTo('bench');

    return is_null($loop) ? $transport : $transport->resolvesLoopWith(fn (): Loop => $loop);
}

/** A timer-sampled pin on device "bench", resolving $loop (or no loop). */
function sampledPin(?Loop $loop, array $levels, int $pin = 5, float $interval = 0.002): SampledDigitalInputTransport
{
    $transport = (new SampledDigitalInputTransport($pin, $interval))->boundTo('bench');
    $transport->levels = $levels;

    return is_null($loop) ? $transport : $transport->resolvesLoopWith(fn (): Loop => $loop);
}

/** Port "bench" on a fake driver, connected at $baud, bound to $loop when given. */
function uartPort(?Loop $loop = null, int $baud = 115_200): FakeUARTTransport
{
    $driver = new FakeUARTConnectionDriver;

    if (! is_null($loop)) {
        $driver->resolvesLoopWith(fn (): Loop => $loop);
    }

    $driver->connectTo('bench')->baud($baud)->register();

    return $driver->device('bench');
}
