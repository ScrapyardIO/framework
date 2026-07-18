<?php

namespace GeneralPurposeIO\Contracts\UART;

use GeneralPurposeIO\Contracts\Common\GPIOException;

class UARTException extends GPIOException
{
    public static function missingMasterDevice(): static
    {
        return new static('UART Port device is missing.');
    }

    public static function couldNotOpenUARTPort(string|int $path): static
    {
        return new static("{$path} could not be opened.");
    }

    public static function unsupportedDevice(string $device): static
    {
        return new static("Unsupported usb uart device '{$device}'.");
    }

    public static function couldNotOpenDevice(string $device, string $error = ''): static
    {
        $message = "Could not open usb uart device '{$device}'.";
        if ($error !== '') {
            $message .= " FTDI error: {$error}";
        }

        return new static($message);
    }
}
