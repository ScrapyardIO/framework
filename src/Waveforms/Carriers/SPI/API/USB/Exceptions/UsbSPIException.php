<?php

namespace Waveforms\Carriers\SPI\API\USB\Exceptions;

use Waveforms\Carriers\SPI\Exceptions\SPIException;

class UsbSPIException extends SPIException
{
    public static function unsupportedDevice(string $device): static
    {
        return new static("Unsupported usb spi device '{$device}'.");
    }

    public static function missingDevice(): static
    {
        return new static('Missing usb spi device.');
    }

    public static function unsupportedChannel(string $device, int $channel, int $available): static
    {
        return new static(
            "{$device} exposes {$available} MPSSE channel(s); channel {$channel} requested."
        );
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
