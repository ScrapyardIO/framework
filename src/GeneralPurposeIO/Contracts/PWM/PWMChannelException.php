<?php

namespace GeneralPurposeIO\Contracts\PWM;

use GeneralPurposeIO\Contracts\Common\GPIOException;

class PWMChannelException extends GPIOException
{
    public static function missingPWMChipDevice(): static
    {
        return new static('PWM Chip device is missing.');
    }

    public static function missingChannelOffset(): static
    {
        return new static('PWM Chip offset is missing.');
    }

    public static function chipNotFound(int|string $chip): static
    {
        return new static("PWM chip pwmchip{$chip} was not found under /sys/class/pwm.");
    }

    public static function couldNotExport(int|string $chip, int $channel): static
    {
        return new static("Could not export PWM channel {$channel} on pwmchip{$chip}.");
    }

    public static function channelNotReady(string $path): static
    {
        return new static("PWM channel attribute is not writable yet: {$path}");
    }

    public static function couldNotWrite(string $path): static
    {
        return new static("Could not write PWM sysfs attribute: {$path}");
    }

    public static function couldNotRead(string $path): static
    {
        return new static("Could not read PWM sysfs attribute: {$path}");
    }

    public static function invalidPolarity(string $value): static
    {
        return new static("Invalid PWM polarity '{$value}'. Expected 'normal' or 'inversed'.");
    }
}
