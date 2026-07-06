<?php

namespace GPIO\Contracts\Digital;

enum LineBias: int
{
    case AS_IS = 1;
    case UNKNOWN = 2;
    case DISABLED = 3;
    case PULL_UP = 4;
    case PULL_DOWN = 5;
}
