<?php

namespace GeneralPurposeIO\Contracts\SPI;

use GeneralPurposeIO\Contracts\Core\GPIOLevelException;

class SPIException extends GPIOLevelException
{
    public static function missingMasterDevice(): static
    {
        return new static('SPI Master device is missing.');
    }

    public static function couldNotOpenSPIDevice(int|string $master, int $chip_select): static
    {
        return new static("/dev/spidev{$master}.{$chip_select} could not be opened.");
    }

    public static function couldNotOpenMpsseContext(string $device, string $error): static
    {
        return new static("MPSSE SPI context for {$device} could not be opened. {$error}");
    }

    public static function missingGpioChipForDigitalPins(): static
    {
        return new static('digitalPins($chip) is required when bundling POSIX digital pins on an SPI bus.');
    }

    public static function noDriverConfigured(): static
    {
        return new static('No SPI connection driver is configured. Set gpio.protocols.spi.default to an installed adapter.');
    }

    public static function alreadyConnected(int|string $device): static
    {
        return new static("SPI device {$device} is already connected.");
    }

    public static function transportClosed(int $chip_select): static
    {
        return new static("SPI chip select {$chip_select} is closed.");
    }

    public static function busHeld(int|string $device, int $holder): static
    {
        return new static("SPI device {$device} is held by chip select {$holder}'s select(): only that slave may use it until select() returns.");
    }

    public static function lsbFirstUnsupported(int|string $device, int $bits_per_word): static
    {
        return new static("SPI device {$device} cannot send {$bits_per_word}-bit words LSB first: the controller refuses it, and bits are reversed in software only for 8-bit words.");
    }

    public static function speedRefused(string $path, int $hz): static
    {
        return new static("{$path} refused a clock of {$hz} Hz.");
    }

    public static function invalidChipSelectPin(int|string $device, int $pin): static
    {
        return new static("Pin {$pin} cannot be a chip select on {$device}: use a DigitalIO pin, 0-3 (D4-D7) or 4-11 (C0-C7).");
    }

    public static function bridgeInUse(string $device): static
    {
        return new static("{$device} is already open for I2C or DigitalIO: its one MPSSE engine runs one protocol at a time.");
    }

    public static function notAttached(int $chip_select): static
    {
        return new static("SPI chip select {$chip_select} was not handed out by a connection driver, so it cannot be offloaded.");
    }

    public static function offloadTargetUnsupported(string $target): static
    {
        return new static("MPSSE SPI transfers run on the device's own USB pump; they cannot go to the [{$target}] work target.");
    }

    public static function busSettingsUnknown(int|string $device): static
    {
        return new static("SPI device {$device} was registered without its connection factory, so a worker cannot open it with the same settings: connect it with connectTo(...)->register().");
    }

    public static function busLockUnavailable(string $path): static
    {
        return new static("The SPI bus lock {$path} could not be opened or taken.");
    }
}
