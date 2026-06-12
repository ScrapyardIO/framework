<?php

namespace ScrapyardIO\NutsAndBolts\DataObjects;

use InvalidArgumentException;
use OutOfBoundsException;
use SplFixedArray;

/**
 * A format-agnostic, flat-backed grid of logical pixel values.
 *
 * Cells hold raw ints whose meaning lives in the canonical colour space of
 * whatever produced them (a palette index, a packed colour, etc.). The grid
 * deliberately knows nothing about bit depth, byte orientation, or any device
 * data shape; that interpretation belongs to a later format/transcode layer.
 *
 * Storage is a single SplFixedArray indexed row-major as (y * width + x) to
 * avoid the per-element overhead of nested PHP arrays at panel resolutions.
 */
class PixelGrid
{
    /**
     * @var SplFixedArray<int>
     */
    protected SplFixedArray $cells;

    public function __construct(
        public readonly int $width,
        public readonly int $height,
        int $fill = 0,
    ) {
        if (($width < 1) || ($height < 1)) {
            throw new InvalidArgumentException("PixelGrid dimensions must be positive, got {$width}x{$height}.");
        }

        $this->cells = new SplFixedArray($width * $height);
        $this->fill($fill);
    }

    public function fill(int $value): static
    {
        $size = $this->width * $this->height;
        for ($i = 0; $i < $size; $i++) {
            $this->cells[$i] = $value;
        }

        return $this;
    }

    public function clear(int $value = 0): static
    {
        return $this->fill($value);
    }

    public function contains(int $x, int $y): bool
    {
        return ($x >= 0) && ($x < $this->width) && ($y >= 0) && ($y < $this->height);
    }

    public function get(int $x, int $y): int
    {
        if (! $this->contains($x, $y)) {
            throw new OutOfBoundsException("Coordinate ({$x}, {$y}) is outside the {$this->width}x{$this->height} grid.");
        }

        return $this->cells[($y * $this->width) + $x];
    }

    public function set(int $x, int $y, int $value): static
    {
        if (! $this->contains($x, $y)) {
            throw new OutOfBoundsException("Coordinate ({$x}, {$y}) is outside the {$this->width}x{$this->height} grid.");
        }

        $this->cells[($y * $this->width) + $x] = $value;

        return $this;
    }

    /**
     * The grid as a 2D, row-major array of ints.
     *
     * @return array<int, array<int, int>>
     */
    public function toArray(): array
    {
        $rows = [];

        for ($y = 0; $y < $this->height; $y++) {
            $row = [];
            for ($x = 0; $x < $this->width; $x++) {
                $row[$x] = $this->cells[($y * $this->width) + $x];
            }
            $rows[$y] = $row;
        }

        return $rows;
    }

    /**
     * The grid as a flat, row-major list of ints.
     *
     * @return array<int, int>
     */
    public function values(): array
    {
        return $this->cells->toArray();
    }
}
