<?php

namespace GPIO\Contracts\SPI;

enum SPIMode: int
{
    case MODE_0 = 0;
    case MODE_1 = 1;
    case MODE_2 = 2;
    case MODE_3 = 3;
}
