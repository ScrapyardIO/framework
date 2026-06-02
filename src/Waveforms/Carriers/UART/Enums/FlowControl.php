<?php

namespace Waveforms\Carriers\UART\Enums;

enum FlowControl: int
{
    case NONE = 0;
    case HARDWARE = 1;
    case SOFTWARE = 2;
}
