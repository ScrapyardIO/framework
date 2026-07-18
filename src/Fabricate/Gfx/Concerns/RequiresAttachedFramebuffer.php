<?php

namespace Fabricate\Gfx\Concerns;

use Fabricate\Contracts\Framebuffers\DumpedBuffer;
use Fabricate\Contracts\Framebuffers\FormatSpec;
use Fabricate\Contracts\Framebuffers\Framebuffer;
use Fabricate\Contracts\Gfx\RendererException;
use Fabricate\Framebuffers\FullFramebuffer;

/**
 * Temporary stubs so unbound engines (phpdafruit/glfw) satisfy Renderer2D
 * until their full draw paths are restored. SDL3 overrides these with real work.
 */
trait RequiresAttachedFramebuffer
{
    protected ?Framebuffer $buffer = null;

    public function useFramebuffer(Framebuffer $framebuffer): static
    {
        $this->buffer = $framebuffer;

        return $this;
    }

    public function buffer(): Framebuffer
    {
        return $this->requireBuffer();
    }

    /**
     * @return array<int, DumpedBuffer>
     */
    public function render(): array
    {
        $buffer = $this->requireBuffer();

        if (! method_exists($buffer, 'dump')) {
            throw RendererException::framebufferNotAttached(static::class);
        }

        return $buffer->dump();
    }

    public static function preferredFramebuffer(FormatSpec $format_spec, int $width, int $height): Framebuffer
    {
        return new FullFramebuffer($width, $height, $format_spec);
    }

    public function drawPixel(int $x, int $y, int $color): static
    {
        $this->requireBuffer()->setPixel($x, $y, $color);

        return $this;
    }

    /**
     * @param  array<int, array{0: int, 1: int, 2: int}>  $pixels
     */
    public function drawPixels(array $pixels): static
    {
        foreach ($pixels as [$x, $y, $color]) {
            $this->drawPixel($x, $y, $color);
        }

        return $this;
    }

    public function drawLine(int $x0, int $y0, int $x1, int $y1, int $color): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    public function drawHorizontalLine(int $x, int $y, int $w, int $color): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    public function drawVerticalLine(int $x, int $y, int $h, int $color): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    /**
     * @param  array<int, array{0: int, 1: int, 2: int, 3: int, 4: int}>  $lines
     */
    public function drawLines(array $lines): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    public function drawRect(int $x, int $y, int $w, int $h, int $color): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    public function fillRect(int $x, int $y, int $w, int $h, int $color): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    public function drawRoundRect(int $x, int $y, int $w, int $h, int $r, int $color): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    public function fillRoundRect(int $x, int $y, int $w, int $h, int $r, int $color): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    public function drawCircle(int $x0, int $y0, int $r, int $color): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    public function fillCircle(int $x0, int $y0, int $r, int $color): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    public function drawEllipse(int $x0, int $y0, int $rw, int $rh, int $color): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    public function fillEllipse(int $x0, int $y0, int $rw, int $rh, int $color): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    public function drawTriangle(int $x0, int $y0, int $x1, int $y1, int $x2, int $y2, int $color): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    public function fillTriangle(int $x0, int $y0, int $x1, int $y1, int $x2, int $y2, int $color): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    public function fill(int $color): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    public function setCursor(int $x, int $y): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    public function setTextSize(int $s, ?int $y = null): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    public function setTextColor(int $color, ?int $bg = null): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    public function setTextWrap(bool $wrap): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    public function setCp437(bool $enable): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    public function write(int $c): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    public function drawChar(int $x, int $y, int $c, int $color, int $bg, int $size_x, int $size_y): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    public function print(string $str): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    public function println(string $str = ''): static
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    /**
     * @return array{x1: int, y1: int, w: int, h: int}
     */
    public function getTextBounds(string $str, int $x, int $y): array
    {
        throw RendererException::framebufferNotAttached(static::class);
    }

    protected function requireBuffer(): Framebuffer
    {
        if (is_null($this->buffer)) {
            throw RendererException::framebufferNotAttached(static::class);
        }

        return $this->buffer;
    }
}
