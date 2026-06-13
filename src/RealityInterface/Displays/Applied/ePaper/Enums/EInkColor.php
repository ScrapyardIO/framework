<?php

namespace RealityInterface\Displays\Applied\ePaper\Enums;

/**
 * The logical colours an electronic-ink surface can be drawn with.
 *
 * Values are the canonical palette indices used while drawing; a panel's
 * {@see \ScrapyardIO\NutsAndBolts\DataObjects\ChannelPalette} maps each colour
 * onto the physical RAM plane(s) it owns. WHITE is the background (no plane
 * bit set), so panels that lack a given colour simply never receive it.
 */
enum EInkColor: int
{
    case BLACK = 0;

    case WHITE = 1;

    case RED = 2;

    case YELLOW = 3;
}
