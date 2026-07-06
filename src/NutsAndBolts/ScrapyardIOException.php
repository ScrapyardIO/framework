<?php

namespace ScrapyardIO\NutsAndBolts;

use Exception;

class ScrapyardIOException extends Exception
{
    public static function invalidProperty(string $property, string $circuit_class): static
    {
        return new static("{$circuit_class} does not implement a {$property} property.");
    }

    public static function invalidStaticMethod(string $property, string $circuit_class): static
    {
        return new static("{$circuit_class} does not implement a static {$property} method.");
    }
}
