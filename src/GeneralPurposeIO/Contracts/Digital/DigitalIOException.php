<?php

namespace GeneralPurposeIO\Contracts\Digital;

use GeneralPurposeIO\Contracts\Core\GPIOLevelException;

class DigitalIOException extends GPIOLevelException
{
    public static function missingDigitalPinDevice(): static
    {
        return new static("DigitalPin device is missing.");
    }

    public static function missingDigitalPinOffset(): static
    {
        return new static("DigitalPin offset is missing.");
    }

    public static function noDriverConfigured(): static
    {
        return new static('No DigitalIO connection driver is configured. Set gpio.protocols.digital-in.default to an installed adapter.');
    }

    public static function pinClosed(int $pin, string|int|null $device = null): static
    {
        return new static(is_null($device) ? "Pin {$pin} is closed." : "Pin {$pin} on device {$device} is closed.");
    }

    public static function noEventLoop(): static
    {
        return new static('No Event Loop is configured.');
    }

    public static function lineReadFailed(string $chip_path, int $pin): static
    {
        return new static("Could not read the level of line {$pin} on {$chip_path}.");
    }

    public static function edgeReadFailed(string $chip_path, int $pin): static
    {
        return new static("Could not read edge events for line {$pin} on {$chip_path}.");
    }

    public static function edgeStreamFailed(string $chip_path, int $pin): static
    {
        return new static("Could not open an edge stream for line {$pin} on {$chip_path}.");
    }

    public static function pinsReadFailed(int $pin): static
    {
        return new static("Could not read pin {$pin}: the MPSSE device did not answer.");
    }
}
