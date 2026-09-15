<?php

namespace GeneralPurposeIO\UART\Drivers;

use GeneralPurposeIO\Contracts\UART\UARTDriver as DriverContract;

abstract class UARTDriver implements DriverContract
{
    protected static function normalizeData(array|string $data): string
    {
        return is_array($data) ? array2bytes($data) : $data;
    }
}
