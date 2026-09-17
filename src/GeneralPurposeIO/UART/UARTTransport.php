<?php

namespace GeneralPurposeIO\UART;

use GeneralPurposeIO\Contracts\Core\ByteSource;
use GeneralPurposeIO\Contracts\UART\UARTTransport as TransportContract;

/** Every port is a ByteSource the gpio dock can receive from. */
abstract class UARTTransport implements TransportContract, ByteSource
{
    abstract public function handle(): mixed;

    protected static function normalizeData(array|string $data): string
    {
        return is_array($data) ? array2bytes($data) : $data;
    }
}