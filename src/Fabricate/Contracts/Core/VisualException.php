<?php

namespace Fabricate\Contracts\Core;

use Fabricate\NutsAndBolts\ScrapyardIOException;

class VisualException extends ScrapyardIOException
{
    public static function unsupportedDisplayType(string $type): static
    {
        return new static("Visual display type [{$type}] is not supported.");
    }

    public static function invalidDisplayArguments(string $type): static
    {
        return new static("Visual display type [{$type}] received invalid arguments.");
    }

    public static function missingComponent(string $component): static
    {
        return new static("A {$component} must be selected before creating a visual presentation.");
    }

    public static function invalidRenderer(string $class): static
    {
        return new static("Renderer [{$class}] is not a two-dimensional renderer.");
    }

    public static function incompatible(string $left, string $right): static
    {
        return new static("Visual components [{$left}] and [{$right}] are not compatible.");
    }

    public static function methodUnavailable(string $component, string $method): static
    {
        return new static("Method [{$method}] is not callable on visual {$component}.");
    }

    public static function invalidMainDisplay(string $reason): static
    {
        return new static("Invalid displays.main configuration: {$reason}");
    }
}
