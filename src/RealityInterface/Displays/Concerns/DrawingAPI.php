<?php

namespace RealityInterface\Displays\Concerns;

use RealityInterface\Displays\Renderers\DisplayRenderer;

/**
 * Screen's public drawing surface. Every call is forwarded to the attached
 * renderer; Screen stays chainable while the renderer does the real work.
 *
 * @property DisplayRenderer $renderer
 */
trait DrawingAPI
{
    public function drawPixel(int $x, int $y, int $color): static
    {
        $this->renderer->drawPixel($x, $y, $color);

        return $this;
    }

    /**
     * @param  array<int, array{0: int, 1: int, 2: int}>  $pixels
     */
    public function drawPixels(array $pixels): static
    {
        $this->renderer->drawPixels($pixels);

        return $this;
    }

    public function drawLine(int $x0, int $y0, int $x1, int $y1, int $color): static
    {
        $this->renderer->drawLine($x0, $y0, $x1, $y1, $color);

        return $this;
    }

    public function drawHLine(int $x, int $y, int $w, int $color): static
    {
        $this->renderer->drawHLine($x, $y, $w, $color);

        return $this;
    }

    public function drawVLine(int $x, int $y, int $h, int $color): static
    {
        $this->renderer->drawVLine($x, $y, $h, $color);

        return $this;
    }

    /**
     * @param  array<int, array{0: int, 1: int, 2: int, 3: int, 4: int}>  $lines
     */
    public function drawLines(array $lines): static
    {
        $this->renderer->drawLines($lines);

        return $this;
    }

    public function drawRect(int $x, int $y, int $w, int $h, int $color): static
    {
        $this->renderer->drawRect($x, $y, $w, $h, $color);

        return $this;
    }

    public function drawRoundRect(int $x, int $y, int $w, int $h, int $r, int $color): static
    {
        $this->renderer->drawRoundRect($x, $y, $w, $h, $r, $color);

        return $this;
    }

    public function fill(int $color): static
    {
        $this->renderer->fill($color);

        return $this;
    }

    public function fillRect(int $x, int $y, int $w, int $h, int $color): static
    {
        $this->renderer->fillRect($x, $y, $w, $h, $color);

        return $this;
    }

    public function fillRoundRect(int $x, int $y, int $w, int $h, int $r, int $color): static
    {
        $this->renderer->fillRoundRect($x, $y, $w, $h, $r, $color);

        return $this;
    }

    public function fillCircle(int $x0, int $y0, int $r, int $color): static
    {
        $this->renderer->fillCircle($x0, $y0, $r, $color);

        return $this;
    }

    public function drawCircle(int $x0, int $y0, int $r, int $color): static
    {
        $this->renderer->drawCircle($x0, $y0, $r, $color);

        return $this;
    }

    public function drawEllipse(int $x0, int $y0, int $rw, int $rh, int $color): static
    {
        $this->renderer->drawEllipse($x0, $y0, $rw, $rh, $color);

        return $this;
    }

    public function drawTriangle(int $x0, int $y0, int $x1, int $y1, int $x2, int $y2, int $color): static
    {
        $this->renderer->drawTriangle($x0, $y0, $x1, $y1, $x2, $y2, $color);

        return $this;
    }

    public function fillTriangle(int $x0, int $y0, int $x1, int $y1, int $x2, int $y2, int $color): static
    {
        $this->renderer->fillTriangle($x0, $y0, $x1, $y1, $x2, $y2, $color);

        return $this;
    }

    public function setCursor(int $x, int $y): static
    {
        $this->renderer->setCursor($x, $y);

        return $this;
    }

    public function setTextSize(int $s, ?int $y = null): static
    {
        $this->renderer->setTextSize($s, $y);

        return $this;
    }

    public function setTextColor(int $color, ?int $bg = null): static
    {
        $this->renderer->setTextColor($color, $bg);

        return $this;
    }

    public function setTextWrap(bool $wrap): static
    {
        $this->renderer->setTextWrap($wrap);

        return $this;
    }

    public function setCp437(bool $enable): static
    {
        $this->renderer->setCp437($enable);

        return $this;
    }

    public function write(int $c): static
    {
        $this->renderer->write($c);

        return $this;
    }

    public function drawChar(int $x, int $y, int $c, int $color, int $bg, int $size_x, int $size_y): static
    {
        $this->renderer->drawChar($x, $y, $c, $color, $bg, $size_x, $size_y);

        return $this;
    }

    public function print(string $str): static
    {
        $this->renderer->print($str);

        return $this;
    }

    public function println(string $str = ''): static
    {
        $this->renderer->println($str);

        return $this;
    }

    /**
     * @return array{x1: int, y1: int, w: int, h: int}
     */
    public function getTextBounds(string $str, int $x, int $y): array
    {
        return $this->renderer->getTextBounds($str, $x, $y);
    }
}
