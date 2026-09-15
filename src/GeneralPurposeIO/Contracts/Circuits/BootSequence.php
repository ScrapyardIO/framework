<?php

namespace GeneralPurposeIO\Contracts\Circuits;

interface BootSequence
{
    public function boot(): void;
    public function hasBooted(): bool;
}
