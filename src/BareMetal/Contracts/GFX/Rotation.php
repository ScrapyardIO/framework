<?php

namespace BareMetal\Contracts\GFX;

/**
 * Quarter-turn display rotation, backed by the Adafruit-style quadrant int
 * (0-3) so renderers can keep using bitwise parity checks like `value & 1`.
 */
enum Rotation: int
{
    case DEG_0 = 0;

    case DEG_90 = 1;

    case DEG_180 = 2;

    case DEG_270 = 3;
}
