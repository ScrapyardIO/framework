<?php

namespace Waveforms\Carriers\I2C\API\USB\Exceptions;

use Waveforms\Carriers\I2C\Exceptions\I2CException;

class UsbI2CException extends I2CException
{
    public static function unsupportedDevice(string $device): static
    {
        return new static("Unsupported usb i2c device '{$device}'.");
    }

    public static function missingDevice(): static
    {
        return new static('Missing usb i2c device.');
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
