<?php

namespace GPIO\Contracts\UART;

enum FlowControl: int
{
    case NONE = 0;
    case HARDWARE = 1;
    case SOFTWARE = 2;
}
