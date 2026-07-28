<?php

namespace Fabricate\Contracts\Sensors\Enums;

enum DistanceUnit
{
    case uM;
    case nM;
    case MM;
    case CM;
    case M;

    case IN;
    case FT;
    case YD;
}
