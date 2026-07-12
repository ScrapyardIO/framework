<?php

namespace BareMetal\Actuation\HumanInput\Enums;

enum ButtonHoldThreshold: int
{
    case SHORT = 250;
    case DEFAULT = 500;
    case LONG = 1000;
}
