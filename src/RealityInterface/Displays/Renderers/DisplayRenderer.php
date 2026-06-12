<?php

namespace RealityInterface\Displays\Renderers;

use ScrapyardIO\NutsAndBolts\DataObjects\DumpedBuffer;

/**
 * The common rendering contract every renderer (software GFX, SDL3, …) exposes.
 *
 * Screen draws against this surface, so the methods here are intentionally
 * renderer-agnostic: scalars and plain arrays only, no engine-specific types.
 */
abstract class DisplayRenderer
{
    abstract public function drawPixel(int $x, int $y, int $color): static;

    /**
     * @param  array<int, array{0: int, 1: int, 2: int}>  $pixels  Each entry is [x, y, color]
     */
    abstract public function drawPixels(array $pixels): static;

    abstract public function drawLine(int $x0, int $y0, int $x1, int $y1, int $color): static;

    abstract public function drawHLine(int $x, int $y, int $w, int $color): static;

    abstract public function drawVLine(int $x, int $y, int $h, int $color): static;

    /**
     * @param  array<int, array{0: int, 1: int, 2: int, 3: int, 4: int}>  $lines  Each entry is [x0, y0, x1, y1, color]
     */
    abstract public function drawLines(array $lines): static;

    abstract public function drawRect(int $x, int $y, int $w, int $h, int $color): static;

    abstract public function drawRoundRect(int $x, int $y, int $w, int $h, int $r, int $color): static;

    abstract public function fill(int $color): static;

    abstract public function fillRect(int $x, int $y, int $w, int $h, int $color): static;

    abstract public function fillRoundRect(int $x, int $y, int $w, int $h, int $r, int $color): static;

    abstract public function fillCircle(int $x0, int $y0, int $r, int $color): static;

    abstract public function drawCircle(int $x0, int $y0, int $r, int $color): static;

    abstract public function drawEllipse(int $x0, int $y0, int $rw, int $rh, int $color): static;

    abstract public function drawTriangle(int $x0, int $y0, int $x1, int $y1, int $x2, int $y2, int $color): static;

    abstract public function fillTriangle(int $x0, int $y0, int $x1, int $y1, int $x2, int $y2, int $color): static;

    abstract public function setCursor(int $x, int $y): static;

    abstract public function setTextSize(int $s, ?int $y = null): static;

    abstract public function setTextColor(int $color, ?int $bg = null): static;

    abstract public function setTextWrap(bool $wrap): static;

    abstract public function setCp437(bool $enable): static;

    abstract public function write(int $c): static;

    abstract public function drawChar(int $x, int $y, int $c, int $color, int $bg, int $size_x, int $size_y): static;

    abstract public function print(string $str): static;

    abstract public function println(string $str = ''): static;

    /**
     * @return array{x1: int, y1: int, w: int, h: int}
     */
    abstract public function getTextBounds(string $str, int $x, int $y): array;

    /**
     * @return array<int, DumpedBuffer>
     */
    abstract public function render(): array;
}
