<?php

namespace GeneralPurposeIO\SPI\Drivers;

abstract class SPIDriver
{
    abstract public function close(): void;

    abstract public function read(int $len): array|false;

    abstract public function write(array|string $data): int;

    abstract public function transfer(array|string $data): array|false;
}
