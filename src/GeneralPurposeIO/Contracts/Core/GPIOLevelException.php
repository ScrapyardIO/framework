<?php

namespace GeneralPurposeIO\Contracts\Core;

use RuntimeException;

/** Root of every exception this framework throws. Catch one type without naming a protocol. */
class GPIOLevelException extends RuntimeException
{
    public static function invalidProperty(string $name, string $class): static
    {
        return new static("Invalid property [{$name}] on [{$class}]");
    }
}
