<?php

namespace GeneralPurposeIO\Digital\Adapters;

use GeneralPurposeIO\Contracts\Common\GPIOException;
use Microscrap\Bindings\MPSSE\Enums\MpsseSupportedDevice;
use GeneralPurposeIO\Digital\DigitalIOCommunicationManager;
use GeneralPurposeIO\Digital\Factory\MpsseDigitalIOFactory;

class MpsseDigitalIOAdapter extends DigitalIOCommunicationManager
{
    public function device(string|MpsseSupportedDevice $device): MpsseDigitalIOFactory
    {
        if(is_string($device)) {
            $device = MpsseSupportedDevice::from($device);
        }

        return new MpsseDigitalIOFactory($device);
    }

    protected function confirmDependencies(): void
    {
        if (!extension_loaded('ftdi')) {
            throw new GPIOException('The DigitalIO USB adapter requires the ext-ftdi extension. Install it with pie install php-io-extension/ftdi');
        }

        if(!function_exists('mpsse_open'))
        {
            throw new GPIOException("The DigitalIO USB adapter requires the MPSSE package. Require it with composer require microscrap/mpsse");
        }
    }
}