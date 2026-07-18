<?php

namespace Fabricate\Contracts\Displays;

interface DisplayFactory
{
    public function embedded(string $driver): DisplayFactory;
    public function windowed(string $driver): DisplayFactory;
}
