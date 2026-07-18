<?php

namespace GeneralPurposeIO\PWM;

use Fabricate\Chassis\EntryNotFoundException;
use Fabricate\Contracts\Chassis\CircularDependencyException;
use Fabricate\NutsAndBolts\Manager;
use GeneralPurposeIO\Contracts\Common\CarrierDriverManager;
use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\PWM\Factory\NativePWMDriverFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class PWMCarrierManager extends Manager implements CarrierDriverManager
{
    /**
     * @throws GPIOException
     */
    public function createNativeDriver(): NativePWMDriverFactory
    {
        if (! is_dir('/sys/class/pwm')) {
            throw new GPIOException('The Native PWM driver requires /sys/class/pwm on this machine.');
        }

        return new NativePWMDriverFactory();
    }

    public function adapter(?string $adapter = null)
    {
        return $this->driver($adapter);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws CircularDependencyException
     */
    public function getDefaultDriver()
    {
        return config('gpio.protocols.pwm.default');
    }
}
