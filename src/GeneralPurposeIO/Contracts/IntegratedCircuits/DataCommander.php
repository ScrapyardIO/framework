<?php

namespace GeneralPurposeIO\Contracts\IntegratedCircuits;

interface DataCommander
{
    public function data(array|string $data = []): void;
    public function command(int $register, array $command_data = []): int;
}