<?php

namespace GeneralPurposeIO\Contracts\Digital;

use GeneralPurposeIO\Contracts\NutsAndBolts\GPIOTransport;

interface DigitalOutTransport extends GPIOTransport
{
    public function low(): void;
    public function high(): void;

    public function read(): bool;
    public function write(bool $state): bool;
}