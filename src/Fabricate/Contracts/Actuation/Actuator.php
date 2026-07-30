<?php

namespace Fabricate\Contracts\Actuation;

interface Actuator
{
    public static function circuit(string $driver): static;
}