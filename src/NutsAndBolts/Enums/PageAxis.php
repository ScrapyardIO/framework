<?php

namespace ScrapyardIO\NutsAndBolts\Enums;

/**
 * For paged monochrome packing, the axis a single 8-pixel page byte spans.
 *
 * Vertical pages (8 stacked rows per byte, column-addressed) are the SSD1306 /
 * SH1106 convention; horizontal pages span 8 adjacent columns instead.
 */
enum PageAxis: int
{
    case HORIZONTAL = 0;

    case VERTICAL = 1;
}
