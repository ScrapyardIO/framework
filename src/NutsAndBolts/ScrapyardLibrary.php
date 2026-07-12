<?php

namespace ScrapyardIO\NutsAndBolts;

use RuntimeException;

abstract class ScrapyardLibrary implements ScrapyardIO
{
    abstract protected static function getInstance(): self;
    abstract public static function boot(?array $protocols = null): static;

    protected function __clone(): void
    {
        // Prevent cloning
    }

    public function __wakeup(): void
    {
        throw new RuntimeException('Cannot unserialize singleton');
    }

}
