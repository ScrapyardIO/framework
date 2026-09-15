<?php

namespace GeneralPurposeIO\SPI\Adapters;

use GeneralPurposeIO\Common\ConfirmPOSIXDependencies;
use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\Contracts\SPI\SPIException;
use GeneralPurposeIO\SPI\Factory\PosixSPIFactory;
use GeneralPurposeIO\SPI\SPICommunicationAdapter;

class PosixSPIAdapter extends SPICommunicationAdapter
{
    /**
     * @throws SPIException
     */
    public function device(int $device): PosixSPIFactory
    {
        if (empty(glob("/dev/spidev{$device}.*"))) {
            throw new SPIException("Device {$device} does not exist");
        }

        return new PosixSPIFactory($device);
    }

    protected function confirmDependencies(): void
    {
        ConfirmPOSIXDependencies::run('SPI');

        if (!function_exists('spi_open')) {
            throw new GPIOException('The POSIX SPI adapter requires the SPI package. Require it with composer require microscrap/spi');
        }
    }
}