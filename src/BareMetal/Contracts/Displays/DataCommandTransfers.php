<?php

namespace BareMetal\Contracts\Displays;

interface DataCommandTransfers
{
    public function data(array $data = []): void;
    public function command(int $register, array $command_data = []): int;
}
