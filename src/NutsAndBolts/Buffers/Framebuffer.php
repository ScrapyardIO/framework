<?php

namespace ScrapyardIO\NutsAndBolts\Buffers;

use ScrapyardIO\NutsAndBolts\DataObjects\PixelGrid;

/**
 * The base in-memory drawing surface.
 *
 * A Framebuffer wraps a single {@see PixelGrid} of logical pixel values and
 * exposes the lowest-level write primitives a renderer needs: set one cell,
 * set many cells to one value, set many cells to their own values, and
 * composite another buffer onto this one.
 *
 * Drawing is clipped silently to the buffer's bounds (off-surface writes are
 * dropped), while the underlying grid stays strict. This base intentionally
 * has no notion of a data format: it only exposes the raw grid via the
 * protected {@see rawDump()}. Format-aware emission (dump/flush) is layered on
 * by subclasses.
 */
abstract class Framebuffer
{
    protected PixelGrid $grid;

    public function __construct(
        public readonly int $width,
        public readonly int $height,
    ) {
        $this->grid = new PixelGrid($width, $height);
    }

    abstract public function setSegment(int $x, int $y, int $width, int $height, int $color): static;

    public function viewportHeight(): int
    {
        return $this->height;
    }

    public function viewportWidth(): int
    {
        return $this->width;
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
     * Composite a source buffer onto this one at the given offset.
     */
    public function blitFrom(Framebuffer $source, int $offset_x = 0, int $offset_y = 0): static
    {
        for ($y = 0; $y < $source->height; $y++) {
            for ($x = 0; $x < $source->width; $x++) {
                $this->setPixel($offset_x + $x, $offset_y + $y, $source->grid->get($x, $y));
            }
        }

        return $this;
    }

    public function blitTo(Framebuffer $target, int $offset_x = 0, int $offset_y = 0): static
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
