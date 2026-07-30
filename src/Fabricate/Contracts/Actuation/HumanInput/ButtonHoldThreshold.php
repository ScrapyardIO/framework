<?php

namespace Fabricate\Contracts\Actuation\HumanInput;

enum ButtonHoldThreshold: int
{
    case SHORT = 250;
    case DEFAULT = 500;
    case LONG = 1000;
}
