<?php

namespace GeneralPurposeIO\Contracts\NutsAndBolts;

interface GPIOTransport
{
    public function close(): void;
    public function closed(): bool;
}