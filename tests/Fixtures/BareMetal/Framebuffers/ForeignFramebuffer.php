<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\BareMetal\Framebuffers;

use BareMetal\Contracts\Framebuffers\Framebuffer;

/**
 * A Framebuffer implementation that deliberately does NOT extend the
 * BareMetal\Framebuffers base class, to prove blitting works across any
 * implementation of the contract and never reaches into internals.
 */
class ForeignFramebuffer implements Framebuffer
{
    /** @var array<int, array<int, int>> */
    protected array $cells = [];

    public function __construct(
        protected int $viewport_width,
        protected int $viewport_height,
    ) {}

    public function viewportWidth(): int
    {
        return $this->viewport_width;
    }

    public function viewportHeight(): int
    {
        return $this->viewport_height;
    }

    public function getPixel(int $x, int $y): int
    {
        return $this->cells[$y][$x] ?? 0;
    }

    public function setPixel(int $x, int $y, int $value): static
    {
        if (($x >= 0) && ($x < $this->viewport_width) && ($y >= 0) && ($y < $this->viewport_height)) {
            $this->cells[$y][$x] = $value;
        }

        return $this;
    }

    public function setPixels(array $pixels): static
    {
        foreach ($pixels as [$x, $y, $value]) {
            $this->setPixel($x, $y, $value);
        }

        return $this;
    }

    public function setRegion(array $coordinates, int $value): static
    {
        foreach ($coordinates as [$x, $y]) {
            $this->setPixel($x, $y, $value);
        }

        return $this;
    }

    public function setSegment(int $x, int $y, int $width, int $height, int $color): static
    {
        for ($row = 0; $row < $height; $row++) {
            for ($col = 0; $col < $width; $col++) {
                $this->setPixel($x + $col, $y + $row, $color);
            }
        }

        return $this;
    }

    public function blitFrom(Framebuffer $source, int $offset_x = 0, int $offset_y = 0): Framebuffer
    {
        for ($y = 0; $y < $source->viewportHeight(); $y++) {
            for ($x = 0; $x < $source->viewportWidth(); $x++) {
                $this->setPixel($offset_x + $x, $offset_y + $y, $source->getPixel($x, $y));
            }
        }

        return $this;
    }

    public function blitTo(Framebuffer $target, int $offset_x = 0, int $offset_y = 0): Framebuffer
    {
        return $target->blitFrom($this, $offset_x, $offset_y);
    }
}
