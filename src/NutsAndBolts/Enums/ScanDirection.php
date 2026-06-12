<?php

namespace ScrapyardIO\NutsAndBolts\Enums;

/**
 * The row emission order when serialising a row-major grid to the panel.
 *
 * Horizontal mirroring is a separate concern handled by the downstream
 * converter and is intentionally not modelled here.
 */
enum ScanDirection: int
{
    case TOP_TO_BOTTOM = 0;

    case BOTTOM_TO_TOP = 1;
}
