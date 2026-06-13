<?php

namespace ScrapyardIO\NutsAndBolts\DataObjects;

/**
 * Describes one colour plane of a channel-sorted surface.
 *
 * A channel binds a logical draw colour (an int, typically an
 * {@see \RealityInterface\Displays\Applied\ePaper\Enums\EInkColor} value) to a
 * single 1bpp bit-plane. {@see $inverted} captures the plane's bit polarity:
 * when true a pixel carrying this colour packs as 0 and the background as 1
 * (the SSD1680 black RAM convention); when false the colour packs as 1.
 *
 * This object intentionally holds only ints/bools so the lower NutsAndBolts
 * layer never depends on the higher display colour enums.
 */
readonly class ChannelSpec
{
    public function __construct(
        public int $color,
        public bool $inverted = false,
    ) {}
}
