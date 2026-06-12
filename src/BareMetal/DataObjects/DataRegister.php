<?php

namespace BareMetal\DataObjects;

abstract readonly class DataRegister
{
    abstract public function toBits(): string;

    public function toByte(): int
    {
        $bits = $this->toBits();

        return bindec($bits);
    }

    abstract public static function fromByte(int $byte): static;

    abstract public static function none(): static;
}
