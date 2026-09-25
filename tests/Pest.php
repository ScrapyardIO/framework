<?php

use ScrapyardIO\Tests\Fixtures\SampledDigitalInputTransport;
use ScrapyardIO\Tests\Fixtures\SocketDigitalInputTransport;
use Voyager\Contracts\IOPools\Loop;

pest()->in('Digital', 'I2C');

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
