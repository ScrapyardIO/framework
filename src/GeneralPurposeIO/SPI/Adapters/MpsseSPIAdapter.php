<?php

namespace GeneralPurposeIO\SPI\Adapters;

use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\SPI\Factory\MpsseSPIFactory;
use GeneralPurposeIO\SPI\SPICommunicationAdapter;
use Microscrap\Bindings\MPSSE\Enums\MpsseSupportedDevice;

class MpsseSPIAdapter extends SPICommunicationAdapter
{
    public function device(string|MpsseSupportedDevice $device): MpsseSPIFactory
    {
        if(is_string($device)) {
            $device = MpsseSupportedDevice::from($device);
        }

        return new MpsseSPIFactory($device);
    }

    /**
     * @throws GPIOException
     */
    protected function confirmDependencies(): void
    {
        if (!extension_loaded('ftdi')) {
            throw new GPIOException('The SPI USB adapter requires the ext-ftdi extension. Install it with pie install php-io-extension/ftdi');
        }

        if(!function_exists('mpsse_open'))
        {
            throw new GPIOException("The SPI USB adapter requires the MPSSE package. Require it with composer require microscrap/mpsse");
        }
    }
}