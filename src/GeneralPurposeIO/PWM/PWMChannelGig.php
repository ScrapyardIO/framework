<?php

namespace GeneralPurposeIO\PWM;

use GeneralPurposeIO\Contracts\NutsAndBolts\BusJob;
use Voyager\Contracts\IOPools\WorkerPools\ShouldPool;

/**
 * One channel job, wherever the worker pool runs it. The worker builds the driver by class from the caller's worker
 * arguments, so it reaches the same hardware, and connects the chip itself. The caller exported the channel before
 * it could offload, so the worker only opens it. One driver per class and arguments per process: a worker keeps its
 * chips open between gigs.
 */
final class PWMChannelGig implements ShouldPool
{
    /** @var array<string, PWMConnectionDriver> "<class><serialized arguments>" => the driver */
    private static array $drivers = [];

    /** @param class-string<PWMConnectionDriver> $driver */
    public function __construct(
        public readonly string $driver,
        public readonly array $arguments,
        public readonly string|int $device,
        public readonly int $channel,
        public readonly BusJob $job,
    ) {}

    public function handle(): mixed
    {
        $driver = self::$drivers[$this->driver.serialize($this->arguments)] ??= new ($this->driver)(...$this->arguments);

        if (! $driver->connections->has($this->device)) {
            $driver->connectTo($this->device)->register();
        }

        return $this->job->run($driver->device($this->device, $this->channel));
    }
}
