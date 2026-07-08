<?php

namespace BareMetal\Contracts\Framebuffers;

use BareMetal\Contracts\Framebuffers\DTO\ChannelPalette;
use BareMetal\Contracts\Framebuffers\Enums\BitDepth;
use BareMetal\Contracts\Framebuffers\Enums\BitOrder;
use BareMetal\Contracts\Framebuffers\Enums\Endianness;
use BareMetal\Contracts\Framebuffers\Enums\PageAxis;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Contracts\Framebuffers\Enums\ScanDirection;

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
