<?php

namespace RealityInterface\Displays\Exceptions;

use BareMetal\Exceptions\CircuitRepoException;

class DisplayRepoException extends CircuitRepoException
{
    public static function embeddedDisplayNotRegistered($circuit_name): static
    {
        return new static("Display '$circuit_name' is not registered");
    }
}
