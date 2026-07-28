<?php

namespace Fabricate\Contracts\NutsAndBolts;

interface BootSequence
{
    public function boot(): void;
    public function hasBooted(): bool;
}
