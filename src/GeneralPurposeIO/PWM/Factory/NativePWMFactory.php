<?php

namespace GeneralPurposeIO\PWM\Factory;

use GeneralPurposeIO\Contracts\PWM\PWMException;
use GeneralPurposeIO\PWM\Bus\NativePWMBus;
use GeneralPurposeIO\PWM\Drivers\NativePWMDriver;

class NativePWMFactory
{
    public function __construct(
        protected int $pwm_chip
    ) {}

    /**
     * @throws PWMException
     */
    public function bus(): NativePWMBus
    {
        $driver = $this->driver();

        return new NativePWMBus($driver);
    }

    /**
     * @throws PWMException
     */
    public function driver(): NativePWMDriver
    {
        $this->assertReady();

        return new NativePWMDriver($this->pwm_chip);
    }

    /**
     * @throws PWMException
     */
    protected function assertReady(): void
    {
        if (is_null($this->pwm_chip)) {
            throw PWMException::missingPWMChipDevice();
        }
    }
}