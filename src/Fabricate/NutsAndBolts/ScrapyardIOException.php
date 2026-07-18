<?php

namespace Fabricate\NutsAndBolts;

use Exception;

class ScrapyardIOException extends Exception
{
    public static function invalidProperty(string $name, string $class): static
    {
        return new static("Invalid property [{$name}] on [{$class}]");
    }
}
