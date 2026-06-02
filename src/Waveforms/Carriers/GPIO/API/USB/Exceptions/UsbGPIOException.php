<?php

namespace Waveforms\Carriers\GPIO\API\USB\Exceptions;

use Waveforms\Carriers\GPIO\Exceptions\GPIOException;

class UsbGPIOException extends GPIOException
{
    public static function unsupportedDevice(string $device): static
    {
        return new static("Unsupported usb gpio device '{$device}'.");
    }

    public static function couldNotOpenDeviceContext(string $device, string $error = ''): static
    {
        $message = "Could not open device context '{$device}'.";
        if ($error !== '') {
            $message .= " FTDI error: {$error}";
        }

        return new static($message);
    }
}
