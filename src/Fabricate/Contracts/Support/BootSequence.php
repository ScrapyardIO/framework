<?php

namespace Fabricate\Contracts\Support;

interface BootSequence
{
    public function boot(): void;
    public function hasBooted(): bool;
}
