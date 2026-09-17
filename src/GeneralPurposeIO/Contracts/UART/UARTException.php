<?php

namespace GeneralPurposeIO\Contracts\UART;

use GeneralPurposeIO\Contracts\Core\GPIOLevelException;

class UARTException extends GPIOLevelException
{
    public static function missingMasterDevice(): static
    {
        return new static('UART port device is missing.');
    }

    public static function couldNotOpenUARTPort(string|int $device): static
    {
        return new static("UART port [{$device}] could not be opened.");
    }

    public static function couldNotOpenFtdiDevice(string $device, string $error): static
    {
        return new static("Could not open FTDI UART device [{$device}]. {$error}");
    }

    public static function couldNotConfigureFtdiDevice(string $device, string $operation, string $error): static
    {
        return new static("Could not configure FTDI UART device [{$device}] during [{$operation}]. {$error}");
    }

    public static function couldNotConfigureUARTPort(string $device): static
    {
        return new static("UART port [{$device}] could not be configured.");
    }

    public static function noDriverConfigured(): static
    {
        return new static('No UART connection driver is configured. Set gpio.protocols.uart.default to an installed adapter.');
    }
}
