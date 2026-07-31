<?php

namespace Fabricate\Contracts\UX;

use RuntimeException;

class UXException extends RuntimeException
{
    public static function noPresentation(): static
    {
        return new static('The stage has no visual presentation — Visual::main() returned null, which happens on the console display type.');
    }

    public static function alreadyMounted(string $node): static
    {
        return new static("{$node} is already mounted; detach it before adding it to another parent.");
    }

    public static function notMeasured(string $node): static
    {
        return new static("{$node} was painted before it was measured — the stage must settle layout first.");
    }
}
