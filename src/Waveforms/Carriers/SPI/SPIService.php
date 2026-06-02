<?php

namespace Waveforms\Carriers\SPI;

use Waveforms\Carriers\SPI\API\Native\Factory\NativeSPIConnectionBuilder;
use Waveforms\Carriers\SPI\API\USB\Factory\UsbSPIConnectionBuilder;
use Waveforms\CarrierService;

class SPIService extends CarrierService
{
    protected array $drivers = [
        'native' => [
            'factory' => NativeSPIConnectionBuilder::class,
        ],
        'usb' => [
            'factory' => UsbSPIConnectionBuilder::class,
        ],
    ];
}
