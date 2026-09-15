<?php

namespace GeneralPurposeIO\Digital\Adapters;

use GeneralPurposeIO\Common\ConfirmPOSIXDependencies;
use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\Contracts\Digital\DigitalIOException;
use GeneralPurposeIO\Digital\DigitalIOCommunicationManager;
use GeneralPurposeIO\Digital\Factory\PosixDigitalIOFactory;

class PosixDigitalIOAdapter extends DigitalIOCommunicationManager
{
    /**
     * @throws DigitalIOException
     */
    public function device(int $device): PosixDigitalIOFactory
    {
        if(!file_exists("/dev/gpiochip{$device}"))
        {
            throw new DigitalIOException("Device {$device} does not exist");
        }

        return new PosixDigitalIOFactory($device);
    }

    protected function confirmDependencies(): void
    {
        ConfirmPOSIXDependencies::run('DigitalIO');

        if(!function_exists('gpiod_chip_open'))
        {
            throw new GPIOException("The DigitalIO POSIX adapter requires the GPIO package. Require it with composer require microscrap/gpio");
        }
    }
}