<?php

namespace Fabricate\Contracts\Framebuffers;

use Fabricate\Contracts\Framebuffers\Enums\BitDepth;
use Fabricate\Contracts\Framebuffers\Enums\BitOrder;
use Fabricate\Contracts\Framebuffers\Enums\Endianness;
use Fabricate\Contracts\Framebuffers\Enums\PageAxis;
use Fabricate\Contracts\Framebuffers\Enums\PixelFormat;
use Fabricate\Contracts\Framebuffers\Enums\ScanDirection;

interface FormatSpecFramebufferFactory
{
    public function bitDepth(BitDepth $depth): static;
    public function pageAxis(PageAxis $page_axis): static;
    public function bitOrder(BitOrder $bit_order): static;
    public function palette(ChannelPalette $palette): static;
    public function endianness(Endianness $endianness): static;
    public function pixelFormat(PixelFormat $pixel_format): static;
    public function scanDirection(ScanDirection $scan_direction): static;
    public function build(): Framebuffer;
}
