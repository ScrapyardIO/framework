<?php

namespace ScrapyardIO\NutsAndBolts\Enums;

/**
 * The order in which logical pixel bits are packed within a single byte.
 */
enum BitOrder: int
{
    case MSB_FIRST = 0;

    case LSB_FIRST = 1;
}
