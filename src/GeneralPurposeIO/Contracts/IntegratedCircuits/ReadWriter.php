<?php

namespace GeneralPurposeIO\Contracts\IntegratedCircuits;

interface ReadWriter
{
    public function write(int $register, array $data): int;
    public function read(int $register, int $length): array;
}