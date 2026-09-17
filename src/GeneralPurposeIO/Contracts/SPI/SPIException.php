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
}
