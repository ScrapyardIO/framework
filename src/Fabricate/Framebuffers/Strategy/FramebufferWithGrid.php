<?php

namespace Fabricate\Framebuffers\Strategy;

use Fabricate\Framebuffers\Framebuffer;
use Fabricate\Framebuffers\PixelGrid;

abstract class FramebufferWithGrid extends Framebuffer
{
    protected PixelGrid $grid;

    public function __construct(
        int $width,
        int $height,
    ) {
        parent::__construct($width, $height);
        $this->grid = new PixelGrid($width, $height);
    }

    /**
     * Off-surface reads return 0, mirroring the silent clipping of
     * {@see setPixel()} so blitting never has to bounds-check.
     */
    public function getPixel(int $x, int $y): int
    {
        return $this->grid->contains($x, $y) ? $this->grid->get($x, $y) : 0;
    }

    public function setPixel(int $x, int $y, int $value): static
    {
        if ($this->grid->contains($x, $y)) {
            $this->grid->set($x, $y, $value);
        }

        return $this;
    }
    /**
     * Emit the buffer contents in the rawest form: a 2D, row-major grid of ints.
     *
     * @return array<int, array<int, int>>
     */
    protected function rawDump(): array
    {
        return $this->grid->toArray();
    }
}
