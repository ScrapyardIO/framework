<?php

namespace Fabricate\Contracts\Rendering;

interface DrawingSurface
{
    public function drawPixel(int $x, int $y, int $color): static;

    public function drawPixels(array $pixels): static;

    public function drawLine(int $x0, int $y0, int $x1, int $y1, int $color): static;

    public function drawHorizontalLine(int $x, int $y, int $w, int $color): static;

    public function drawVerticalLine(int $x, int $y, int $h, int $color): static;

    public function drawLines(array $lines): static;

    public function drawRect(int $x, int $y, int $w, int $h, int $color): static;

    public function fillRect(int $x, int $y, int $w, int $h, int $color): static;

    public function drawRoundRect(int $x, int $y, int $w, int $h, int $r, int $color): static;

    public function fillRoundRect(int $x, int $y, int $w, int $h, int $r, int $color): static;

    public function drawCircle(int $x0, int $y0, int $r, int $color): static;

    public function fillCircle(int $x0, int $y0, int $r, int $color): static;

    public function drawEllipse(int $x0, int $y0, int $rw, int $rh, int $color): static;

    public function fillEllipse(int $x0, int $y0, int $rw, int $rh, int $color): static;

    public function drawTriangle(int $x0, int $y0, int $x1, int $y1, int $x2, int $y2, int $color): static;

    public function fillTriangle(int $x0, int $y0, int $x1, int $y1, int $x2, int $y2, int $color): static;

    public function fill(int $color): static;

    public function setCursor(int $x, int $y): static;

    public function setTextSize(int $s, ?int $y = null): static;

    public function setTextColor(int $color, ?int $bg = null): static;

    public function setTextWrap(bool $wrap): static;

    /**
     * @param  \Fabricate\Rendering\Fonts\GFXFont|string|null  $f
     */
    public function setFont(object|string|null $f = null): static;

    public function setCp437(bool $enable): static;

    public function write(int $c): static;

    public function drawChar(int $x, int $y, int $c, int $color, int $bg, int $size_x, int $size_y): static;

    public function print(string $str): static;

    public function println(string $str = ''): static;

    public function getTextBounds(string $str, int $x, int $y): array;
}
