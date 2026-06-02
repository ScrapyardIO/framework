<?php

namespace Waveforms\Carriers\UART\API\USB\Exceptions;

use Waveforms\Carriers\UART\Exceptions\UARTException;

class UsbUARTException extends UARTException
{
    public static function unsupportedDevice(string $device): static
    {
        return new static("Unsupported usb uart device '{$device}'.");
    }

    public static function missingDevice(): static
    {
        return new static('Missing usb uart device.');
    }

    public static function couldNotCreateContext(): static
    {
        return new static('Could not allocate an FTDI context.');
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
