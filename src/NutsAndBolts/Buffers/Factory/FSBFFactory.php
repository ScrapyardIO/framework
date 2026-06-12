<?php

namespace ScrapyardIO\NutsAndBolts\Buffers\Factory;

use Exception;
use ScrapyardIO\NutsAndBolts\Buffers\FormatSpecFrameBuffer;
use ScrapyardIO\NutsAndBolts\DataObjects\FormatSpec;
use ScrapyardIO\NutsAndBolts\Enums\BitDepth;
use ScrapyardIO\NutsAndBolts\Enums\BitOrder;
use ScrapyardIO\NutsAndBolts\Enums\Endianness;
use ScrapyardIO\NutsAndBolts\Enums\PageAxis;
use ScrapyardIO\NutsAndBolts\Enums\PixelFormat;
use ScrapyardIO\NutsAndBolts\Enums\ScanDirection;

abstract class FSBFFactory
{
    public ?PixelFormat $pixel_format = null;

    public ?BitDepth $bit_depth = null;

    public ?ScanDirection $scan_direction = ScanDirection::TOP_TO_BOTTOM;

    public ?BitOrder $bit_order = null;

    public ?Endianness $endianness = null;

    public ?PageAxis $page_axis = null;

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

    /**
     * @throws Exception
     */
    protected function buildFormatSpec(): FormatSpec
    {
        if (! is_null($this->pixel_format)) {
            if (! is_null($this->bit_depth)) {
                return new FormatSpec(
                    $this->pixel_format,
                    $this->bit_depth,
                    $this->scan_direction,
                    $this->bit_order,
                    $this->endianness,
                    $this->page_axis
                );
            }

            throw new Exception('Missing bit depth.');
        }

        throw new Exception('Missing pixel format.');
    }

    abstract public function build(): FormatSpecFrameBuffer;
}
