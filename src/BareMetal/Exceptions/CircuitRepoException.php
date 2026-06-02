<?php

namespace BareMetal\Exceptions;

use RuntimeException;

class CircuitRepoException extends RuntimeException
{
    public static function integratedCircuitNotRegistered($circuit_name): static
    {
        return new static("Circuit '$circuit_name' is not registered");
    }
}
