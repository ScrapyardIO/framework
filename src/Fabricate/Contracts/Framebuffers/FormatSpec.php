<?php

namespace Fabricate\Contracts\Framebuffers;

use Fabricate\Contracts\Framebuffers\ChannelPalette;
use Fabricate\Contracts\Framebuffers\Enums\BitDepth;
use Fabricate\Contracts\Framebuffers\Enums\BitOrder;
use Fabricate\Contracts\Framebuffers\Enums\Endianness;
use Fabricate\Contracts\Framebuffers\Enums\PageAxis;
use Fabricate\Contracts\Framebuffers\Enums\PixelFormat;
use Fabricate\Contracts\Framebuffers\Enums\ScanDirection;

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
 * multi-byte pixels (TFT 16/18/24/32-bit), page axis only for paged monochrome
 * panels, and palette only for channel-sorted (multi-plane ePaper) surfaces.
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
        public ?ChannelPalette $palette = null,
    ) {}
}
