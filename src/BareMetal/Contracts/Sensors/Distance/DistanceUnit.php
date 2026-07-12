<?php

namespace BareMetal\Contracts\Sensors\Distance;

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
