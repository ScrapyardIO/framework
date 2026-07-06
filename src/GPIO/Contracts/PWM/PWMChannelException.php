<?php

namespace GPIO\Contracts\PWM;


use GPIO\Contracts\Common\GPIOException;

class PWMChannelException extends GPIOException
{
    public static function missingPWMChipDevice() : static
    {
        return new static("PWM Chip device is missing.");
    }

    public static function missingChannelOffset(): static
    {
        return new static("PWM Chip offset is missing.");
    }
}
