<?php

namespace GeneralPurposeIO\Contracts\IntegratedCircuits;

interface BootSequence
{
    public function boot(): void;
    public function hasBooted(): bool;
}
