<?php

namespace BareMetal\Framebuffers;

use BareMetal\Contracts\Framebuffers\DTO\PixelGrid;
use BareMetal\Contracts\Framebuffers\Framebuffer as FramebufferContract;

abstract class Framebuffer implements FramebufferContract
{
    protected PixelGrid $grid;

    public function __construct(
        public readonly int $width,
        public readonly int $height,
    ) {
        $this->grid = new PixelGrid($width, $height);
    }

    public function viewportHeight(): int
    {
        return $this->height;
    }

    public function viewportWidth(): int
    {
        return $this->width;
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
     * Set a group of coordinates to a single shared value.
     *
     * @param  array<int, array{0: int, 1: int}>  $coordinates
     */
    public function setRegion(array $coordinates, int $value): static
    {
        foreach ($coordinates as [$x, $y]) {
            $this->setPixel($x, $y, $value);
        }

        return $this;
    }

    /**
     * Set a group of cells, each carrying its own value.
     *
     * @param  array<int, array{0: int, 1: int, 2: int}>  $pixels
     */
    public function setPixels(array $pixels): static
    {
        foreach ($pixels as [$x, $y, $value]) {
            $this->setPixel($x, $y, $value);
        }

        return $this;
    }

    /**
     * Fill a rectangular region with a single value.
     *
     * Off-surface cells are dropped by {@see setPixel()} clipping, and
     * non-positive dimensions write nothing. Buffers that track dirty state
     * (partial-refresh) override this to also record the touched region.
     */
    public function setSegment(int $x, int $y, int $width, int $height, int $color): static
    {
        for ($row = 0; $row < $height; $row++) {
            for ($col = 0; $col < $width; $col++) {
                $this->setPixel($x + $col, $y + $row, $color);
            }
        }

        return $this;
    }

    /**
     * Composite a source buffer onto this one at the given offset.
     *
     * Reads through the Framebuffer contract only, so any implementation can
     * be blitted from — not just siblings of this base class.
     */
    public function blitFrom(FramebufferContract $source, int $offset_x = 0, int $offset_y = 0): FramebufferContract
    {
        for ($y = 0; $y < $source->viewportHeight(); $y++) {
            for ($x = 0; $x < $source->viewportWidth(); $x++) {
                $this->setPixel($offset_x + $x, $offset_y + $y, $source->getPixel($x, $y));
            }
        }

        return $this;
    }

    public function blitTo(FramebufferContract $target, int $offset_x = 0, int $offset_y = 0): FramebufferContract
    {
        return $target->blitFrom($this, $offset_x, $offset_y);
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
