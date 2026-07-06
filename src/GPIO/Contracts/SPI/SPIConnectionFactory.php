<?php

namespace GPIO\Contracts\SPI;

use GPIO\Contracts\Common\GPIOConnectionFactory as GPIOConnectionFactoryContract;

interface SPIConnectionFactory extends GPIOConnectionFactoryContract
{
    public function mode(SPIMode $value): static;

    public function speed(int $value): static;

    public function endianness(SPIEndianness $endianness): static;

    public function chipSelect(int $value): static;

    public function bitsPerByte(int $value): static;

    public function device(int|string $value): static;
}
