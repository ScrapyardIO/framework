<?php

namespace Fabricate\Contracts\Core;

use RuntimeException;

class ScrapyardIOException extends RuntimeException
{
    public static function invalidProperty(string $name, string $class): static
    {
        return new static("Invalid property [{$name}] on [{$class}]");
    }
}
