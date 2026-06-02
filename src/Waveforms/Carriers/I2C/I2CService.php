<?php

namespace Waveforms\Carriers\I2C;

use Waveforms\Carriers\I2C\API\Native\Factory\NativeI2CConnectionBuilder;
use Waveforms\Carriers\I2C\API\USB\Factory\UsbI2CConnectionBuilder;
use Waveforms\CarrierService;

class I2CService extends CarrierService
{
    protected array $drivers = [
        'native' => [
            'factory' => NativeI2CConnectionBuilder::class,
        ],
        'usb' => [
            'factory' => UsbI2CConnectionBuilder::class,
        ],
    ];
}
