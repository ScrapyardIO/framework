<?php

namespace GeneralPurposeIO\PWM\Adapters;

use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\PWM\Factory\NativePWMFactory;
use GeneralPurposeIO\PWM\PWMCommunicationAdapter;

class NativePWMAdapter extends PWMCommunicationAdapter
{
    public function chip(int $pwm_chip): NativePWMFactory
    {
        return new NativePWMFactory($pwm_chip);
    }

    protected function confirmDependencies(): void
    {
        if (! is_dir('/sys/class/pwm')) {
            throw new GPIOException('The Native PWM adapter requires /sys/class/pwm on this machine.');
        }
    }
}