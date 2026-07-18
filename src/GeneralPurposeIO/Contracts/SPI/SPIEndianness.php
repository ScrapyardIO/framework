<?php

namespace GeneralPurposeIO\Contracts\SPI;

enum SPIEndianness: string
{
    case MSB = 'msb';
    case LSB = 'lsb';
}
