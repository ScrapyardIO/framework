<?php

namespace GeneralPurposeIO\Contracts\SPI;

interface SPIAPI
{
    public function read(int $len): array|false;

    public function write(array|string $data): int;

    public function transfer(array|string $data): array|false;
}
