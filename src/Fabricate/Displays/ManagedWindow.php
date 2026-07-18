<?php

namespace Fabricate\Displays;

use Fabricate\Contracts\Framebuffers\FormatSpec;

abstract class ManagedWindow
{
    public function __construct(
        public readonly int $width,
        public readonly int $height,
    ) {}

    abstract public function formatSpec(): FormatSpec;

    public function height(): int
    {
        return $this->height;
    }

    public function width(): int
    {
        return $this->width;
    }
}
