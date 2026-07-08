<?php

namespace BareMetal\Displays;

use BareMetal\Circuits\IntegratedCircuit;
use BareMetal\Contracts\Displays\Display as DisplayContract;
use BareMetal\Contracts\Framebuffers\DTO\DumpedBuffer;
use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;

abstract class Display extends IntegratedCircuit implements DisplayContract
{
    protected FormatSpec $format_spec;

    public function __construct(
        protected int $width,
        protected int $height,
    ) {
        $this->format_spec = $this->generateFormatSpec();
    }

    public function width(): int
    {
        return $this->width;
    }

    public function height(): int
    {
        return $this->height;
    }

    /**
     * The live spec — drivers can regenerate it at runtime (e.g. when the
     * memory addressing mode changes), so callers must never cache the result.
     */
    public function formatSpec(): FormatSpec
    {
        return $this->format_spec;
    }

    abstract public function generateFormatSpec(): FormatSpec;

    abstract public function transmit(DumpedBuffer $frame): void;
}
