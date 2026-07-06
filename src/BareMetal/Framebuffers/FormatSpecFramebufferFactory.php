<?php

namespace BareMetal\Framebuffers;

use BareMetal\Contracts\Framebuffers\DTO\ChannelPalette;
use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\BitDepth;
use BareMetal\Contracts\Framebuffers\Enums\BitOrder;
use BareMetal\Contracts\Framebuffers\Enums\Endianness;
use BareMetal\Contracts\Framebuffers\Enums\PageAxis;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Contracts\Framebuffers\Enums\ScanDirection;
use BareMetal\Contracts\Framebuffers\FormatSpecFramebufferFactory as FormatSpecFramebufferFactoryContract;
use BareMetal\Contracts\Framebuffers\FramebufferException;

abstract class FormatSpecFramebufferFactory implements FormatSpecFramebufferFactoryContract
{
    public ?PixelFormat $pixel_format = null;

    public ?BitDepth $bit_depth = null;

    public ?ScanDirection $scan_direction = ScanDirection::TOP_TO_BOTTOM;

    public ?BitOrder $bit_order = null;

    public ?Endianness $endianness = null;

    public ?PageAxis $page_axis = null;

    public ?ChannelPalette $palette = null;

    public function __construct(
        public int $width,
        public int $height,
    ) {}

    public function pixelFormat(PixelFormat $pixel_format): static
    {
        $this->pixel_format = $pixel_format;

        return $this;
    }

    public function bitDepth(BitDepth $depth): static
    {
        $this->bit_depth = $depth;

        return $this;
    }

    public function scanDirection(ScanDirection $scan_direction): static
    {
        $this->scan_direction = $scan_direction;

        return $this;
    }

    public function bitOrder(BitOrder $bit_order): static
    {
        $this->bit_order = $bit_order;

        return $this;
    }

    public function endianness(Endianness $endianness): static
    {
        $this->endianness = $endianness;

        return $this;
    }

    public function pageAxis(PageAxis $page_axis): static
    {
        $this->page_axis = $page_axis;

        return $this;
    }

    public function palette(ChannelPalette $palette): static
    {
        $this->palette = $palette;

        return $this;
    }

    /**
     * @throws FramebufferException
     */
    protected function buildFormatSpec(): FormatSpec
    {
        if (is_null($this->pixel_format)) {
            throw new FramebufferException('Missing pixel format.');
        }

        if (is_null($this->bit_depth)) {
            throw new FramebufferException('Missing bit depth.');
        }

        $this->assertCoherent($this->pixel_format, $this->bit_depth);

        return new FormatSpec(
            $this->pixel_format,
            $this->bit_depth,
            $this->scan_direction,
            $this->bit_order,
            $this->endianness,
            $this->page_axis,
            $this->palette
        );
    }

    /**
     * Reject FormatSpec combinations that would pack garbage bytes: each
     * packing family has facts it cannot work without, and catching them at
     * build() time beats debugging a scrambled panel.
     *
     * @throws FramebufferException
     */
    protected function assertCoherent(PixelFormat $pixel_format, BitDepth $bit_depth): void
    {
        match ($pixel_format) {
            PixelFormat::MONO_VERTICAL_PAGE, PixelFormat::MONO_HORIZONTAL => $this->assertCoherentMono($pixel_format, $bit_depth),
            PixelFormat::ROW_MAJOR => $this->assertCoherentRowMajor($bit_depth),
            PixelFormat::PLANAR => $this->assertCoherentPlanar(),
        };
    }

    /**
     * @throws FramebufferException
     */
    protected function assertCoherentMono(PixelFormat $pixel_format, BitDepth $bit_depth): void
    {
        if ($bit_depth !== BitDepth::B1) {
            throw new FramebufferException("{$pixel_format->value} packing requires 1-bit depth, got {$bit_depth->value}.");
        }

        if (is_null($this->bit_order)) {
            throw new FramebufferException("{$pixel_format->value} packing requires a bit order.");
        }

        if (($pixel_format === PixelFormat::MONO_VERTICAL_PAGE) && ($this->page_axis === PageAxis::HORIZONTAL)) {
            throw new FramebufferException('mono_vertical_page packing cannot use a horizontal page axis.');
        }
    }

    /**
     * @throws FramebufferException
     */
    protected function assertCoherentRowMajor(BitDepth $bit_depth): void
    {
        if (($bit_depth->value > 8) && is_null($this->endianness)) {
            throw new FramebufferException("row_major packing with {$bit_depth->value}-bit pixels requires an endianness.");
        }
    }

    /**
     * @throws FramebufferException
     */
    protected function assertCoherentPlanar(): void
    {
        if (is_null($this->palette)) {
            throw new FramebufferException('planar packing requires a channel palette.');
        }
    }

    abstract public function build(): FormatSpecFramebuffer;
}
