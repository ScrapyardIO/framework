<?php

namespace ScrapyardIO\NutsAndBolts\DataObjects;

use ScrapyardIO\NutsAndBolts\Enums\BitDepth;
use ScrapyardIO\NutsAndBolts\Enums\BitOrder;
use ScrapyardIO\NutsAndBolts\Enums\Endianness;
use ScrapyardIO\NutsAndBolts\Enums\PageAxis;
use ScrapyardIO\NutsAndBolts\Enums\PixelFormat;
use ScrapyardIO\NutsAndBolts\Enums\ScanDirection;

/**
 * Describes how a block of pixel data is laid out.
 *
 * The same value object describes both what a buffer emits and what a display
 * adapter expects, so a downstream transcoder can compare the two and either
 * convert the data or no-op when they already match.
 *
 * Only pixel format and bit depth are always required. The remaining facts are
 * situational and default to null when they do not apply to a given packing
 * family: bit order is for sub-byte (monochrome/planar) packing, endianness for
 * multi-byte pixels (TFT 16/18/24/32-bit), and page axis only for paged
 * monochrome panels.
 */
readonly class FormatSpec
{
    public function __construct(
        public PixelFormat $pixel_format,
        public BitDepth $bit_depth,
        public ScanDirection $scan_direction = ScanDirection::TOP_TO_BOTTOM,
        public ?BitOrder $bit_order = null,
        public ?Endianness $endianness = null,
        public ?PageAxis $page_axis = null,
    ) {}
}
