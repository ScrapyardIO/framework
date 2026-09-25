<?php

namespace ScrapyardIO\Tests\Fixtures;

use Fiber;
use GeneralPurposeIO\Contracts\NutsAndBolts\BusJob;
use GeneralPurposeIO\NutsAndBolts\BusQueue;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\Promise;

/** A fake whose jobs run in a loop fiber against the real slave, the way an MPSSE bridge's do. */
final class FakeBridgeSPIConnectionDriver extends FakeSPIConnectionDriver
{
    protected function dispatch(string|int $device, int $chip_select, BusJob $job, ?string $target, Loop $loop, BusQueue $queue): Promise
    {
        return $loop->async(function () use ($device, $chip_select, $job, $queue): mixed {
            $queue->claim(Fiber::getCurrent());

            return $job->run($this->device($device, $chip_select));
        });
    }
}
