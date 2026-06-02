<?php

namespace Waveforms\Carriers\UART\API\Native\Exceptions;

use Waveforms\Carriers\UART\Exceptions\UARTException;

class NativeUARTException extends UARTException
{
    public static function missingPort(): static
    {
        return new static('Missing UART port path.');
    }
}
