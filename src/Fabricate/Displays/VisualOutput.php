<?php

namespace Fabricate\Displays;

use Fabricate\Contracts\Displays\VisualOutput as OutputContract;
use Fabricate\Contracts\Framebuffers\FormatSpec;

abstract class VisualOutput implements OutputContract
{
    protected FormatSpec $format_spec;

    public function __construct(
        protected int $width,
        protected int $height,
    ) {
        $this->format_spec = $this->generateFormatSpec();
    }

    abstract public function generateFormatSpec(): FormatSpec;

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
}
