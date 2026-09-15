<?php

namespace GeneralPurposeIO\PWM\Bus;

use GeneralPurposeIO\Contracts\PWM\PWMException;
use GeneralPurposeIO\PWM\Drivers\NativePWMDriver;
use GeneralPurposeIO\PWM\PWMChannel;

class NativePWMBus extends PWMBus
{
    public function __construct(
        protected NativePWMDriver $driver
    ) {}

    /**
     * @throws PWMException
     */
    public function channel(int $channel): PWMChannel
    {
        if (! is_dir($this->driver->chip_path)) {
            throw PWMException::chipNotFound($this->driver->chip);
        }

        $channel_path = "{$this->driver->chip_path}/pwm{$channel}";

        if (! is_dir($channel_path)) {
            $written = @file_put_contents("{$this->driver->chip_path}/export", (string) $channel);
            if ($written === false) {
                throw PWMException::couldNotExport($this->driver->chip, $channel);
            }
        }

        // Kernel creates the channel dir before udev chmods attribute files.
        $this->driver->waitUntilWritable("{$channel_path}/period");

        return new PWMChannel($channel, $this->driver);
    }
}