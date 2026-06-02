<?php

namespace Waveforms\Carriers\UART\Enums;

enum Parity: int
{
    case NONE = 0;
    case ODD = 1;
    case EVEN = 2;
}
