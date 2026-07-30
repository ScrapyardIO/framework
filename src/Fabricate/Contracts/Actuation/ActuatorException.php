<?php

namespace Fabricate\Contracts\Actuation;

use Fabricate\NutsAndBolts\ScrapyardIOException;

class ActuatorException extends ScrapyardIOException
{
    public static function disabled(string $class): static
    {
        return new static("{$class} is disabled — call enable() before actuating it.");
    }

    public static function invalidButtonLayout(string $class): static
    {
        return new static("{$class} requires a layout containing only BasicButton instances.");
    }

    public static function duplicateButtonLabel(string $label, string $class): static
    {
        return new static("Button label [{$label}] is duplicated in {$class}.");
    }

    public static function buttonNotFound(string $label, string $class): static
    {
        return new static("Button [{$label}] was not found in {$class}.");
    }
}