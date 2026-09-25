<?php

namespace GeneralPurposeIO\I2C;

use GeneralPurposeIO\Contracts\NutsAndBolts\BusJob;
use Voyager\Contracts\IOPools\ShouldPool;

/**
 * One bus job, wherever the work target runs it. The worker builds the driver by class and connects the bus
 * itself (i2c-dev allows many opens; the kernel serialises transfers). One driver per class per process, so a
 * worker keeps its buses open between gigs.
 */
final class BusGig implements ShouldPool
{
    /** @var array<class-string<I2CConnectionDriver>, I2CConnectionDriver> */
    private static array $drivers = [];

    /** @param class-string<I2CConnectionDriver> $driver */
    public function __construct(
        public readonly string $driver,
        public readonly string|int $device,
        public readonly int $address,
        public readonly BusJob $job,
    ) {}

    public function handle(): mixed
    {
        $driver = self::$drivers[$this->driver] ??= new ($this->driver)();

        if (! $driver->connections->has($this->device)) {
            $driver->connectTo($this->device)->register();
        }

        return $this->job->run($driver->device($this->device, $this->address));
    }
}
