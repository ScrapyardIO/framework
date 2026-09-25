<?php

namespace GeneralPurposeIO\SPI;

use GeneralPurposeIO\Contracts\NutsAndBolts\BusJob;
use Voyager\Contracts\IOPools\ShouldPool;

/**
 * One SPI bus job, wherever the work target runs it. The worker builds the driver by class, connects the bus with
 * the settings it was registered with where the job was queued, and runs the slave at the clock it had there.
 * One driver per class per process, so a worker keeps its buses open between gigs and reconnects one only when the
 * settings changed.
 */
final class SPIBusGig implements ShouldPool
{
    /** @var array<class-string<SPIConnectionDriver>, SPIConnectionDriver> */
    private static array $drivers = [];

    /** @param class-string<SPIConnectionDriver> $driver */
    public function __construct(
        public readonly string $driver,
        public readonly string|int $device,
        public readonly int $chip_select,
        public readonly SPIBusSettings $settings,
        public readonly ?int $hz,
        public readonly BusJob $job,
    ) {}

    public function handle(): mixed
    {
        $driver = self::$drivers[$this->driver] ??= new ($this->driver)();

        // == on purpose: every gig brings its own copy of the settings
        if ($driver->settingsOf($this->device) != $this->settings) {
            $driver->disconnect($this->device);
            $driver->connectTo($this->device)->configure($this->settings)->register();
        }

        $slave = $driver->device($this->device, $this->chip_select);
        $wanted = $this->hz ?? $this->settings->speed;

        if (($slave->clock() ?? $this->settings->speed) !== $wanted) {
            $slave->speed($wanted);
        }

        return $this->job->run($slave);
    }
}
