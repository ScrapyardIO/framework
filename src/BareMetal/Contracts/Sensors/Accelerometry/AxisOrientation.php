<?php

namespace BareMetal\Contracts\Sensors\Accelerometry;

enum AxisOrientation: int
{
    case X = 1;
    case X_INVERTED = -1;
    case Y = 2;
    case Y_INVERTED = -2;
    case Z = 3;
    case Z_INVERTED = -3;
}
