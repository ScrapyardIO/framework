<?php

namespace GPIO\Contracts\SPI;

interface SPIAPI
{
    public function read(int $len): array|false;
    public function write(string|array $data): int;
    public function transfer(string|array $data): array|false;
}
