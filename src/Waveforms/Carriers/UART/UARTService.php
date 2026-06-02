<?php

namespace Waveforms\Carriers\UART;

use Waveforms\Carriers\UART\API\Native\Factory\NativeUARTConnectionBuilder;
use Waveforms\Carriers\UART\API\USB\Factory\UsbUARTConnectionBuilder;
use Waveforms\CarrierService;

class UARTService extends CarrierService
{
    protected array $drivers = [
        'native' => [
            'factory' => NativeUARTConnectionBuilder::class,
        ],
        'usb' => [
            'factory' => UsbUARTConnectionBuilder::class,
        ],
    ];
}
