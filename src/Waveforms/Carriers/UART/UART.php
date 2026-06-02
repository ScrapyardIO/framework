<?php

namespace Waveforms\Carriers\UART;

use Exception;
use Waveforms\Carriers\UART\Factory\UARTConnectionBuilder;
use Waveforms\WaveformEntry;

class UART extends WaveformEntry
{
    /**
     * @throws Exception
     */
    public static function connection(string $driver): UARTConnectionBuilder
    {
        return UARTService::getInstance()->get($driver);
    }
}
