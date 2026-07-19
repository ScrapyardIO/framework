<?php

namespace Fabricate\Actuation\Enums;

enum InputHoldThreshold: int
{
    case SHORT = 250;
    case DEFAULT = 500;
    case LONG = 1000;
}
