<?php

namespace GeneralPurposeIO\I2C\Adapters;

use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\I2C\Factory\MpsseI2CFactory;
use GeneralPurposeIO\I2C\I2CCommunicationAdapter;
use Microscrap\Bindings\MPSSE\Enums\MpsseSupportedDevice;

class MpsseI2CAdapter extends I2CCommunicationAdapter
{
    public function device(string|MpsseSupportedDevice $device): MpsseI2CFactory
    {
        if(is_string($device)) {
            $device = MpsseSupportedDevice::from($device);
        }

        return new MpsseI2CFactory($device);
    }

    protected function confirmDependencies(): void
    {
        if (!extension_loaded('ftdi')) {
            throw new GPIOException('The I2C USB adapter requires the ext-ftdi extension. Install it with pie install php-io-extension/ftdi');
        }

        if(!function_exists('mpsse_open'))
        {
            throw new GPIOException("The I2C USB adapter requires the MPSSE package. Require it with composer require microscrap/mpsse");
        }
    }
}