<?php

namespace GeneralPurposeIO\Contracts\SPI;

interface SPIDriver
{
    public function read($chip_select, int $len): array|false;
    public function write($chip_select, array|string $data): int;
    public function transfer($chip_select, array|string $data): array|false;
    public function close(): void;
}