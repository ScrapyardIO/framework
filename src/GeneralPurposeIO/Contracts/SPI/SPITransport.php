<?php

namespace GeneralPurposeIO\Contracts\SPI;

use GeneralPurposeIO\Contracts\NutsAndBolts\GPIOTransport;

interface SPITransport extends GPIOTransport
{
    public function read(int $len): array|false;
    public function write(array|string $data): int;
    public function transfer(array|string $data): array|false;
}