<?php

namespace GPIO\Contracts\UART;

enum Parity: int
{
    case NONE = 0;
    case ODD = 1;
    case EVEN = 2;
}
