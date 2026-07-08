<?php

namespace BareMetal\Contracts\Circuits;

interface BootSequence
{
    public function boot(): void;
    public function hasBooted(): bool;
}
