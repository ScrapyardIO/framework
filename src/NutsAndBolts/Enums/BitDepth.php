<?php

namespace ScrapyardIO\NutsAndBolts\Enums;

enum BitDepth: int
{
    case B1 = 1;
    case B8 = 8;
    case B10 = 10;
    case B12 = 12;
    case B16 = 16;
    case B18 = 18;
    case B24 = 24;
    case B32 = 32;
}
