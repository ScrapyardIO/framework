<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\BareMetal\GFX;

use BareMetal\Contracts\Framebuffers\DTO\DumpedBuffer;
use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Contracts\Framebuffers\Framebuffer;
use BareMetal\Framebuffers\DirtyRegionsBuffer;
use BareMetal\Framebuffers\FormatSpecFramebuffer;
use BareMetal\Framebuffers\FullFramebuffer;
use BareMetal\Framebuffers\PageSegmentBuffer;
use BareMetal\GFX\Renderer2D;

/**
 * A minimal Renderer2D double: pixels land in the injected buffer, every
 * proxied call is recorded by name so DisplayComponent tests can assert the
 * facade forwards to its renderer, and render() dumps the buffer like the
 * real software renderer does.
 */
class FakeRenderer extends Renderer2D
{
    /**
     * @var array<int, string>
     */
    public array $calls = [];

    public function __construct(
        protected FormatSpecFramebuffer $buffer,
    ) {}

    protected function record(string $method): static
    {
        $this->calls[] = $method;

        return $this;
    }

    public function drawPixel(int $x, int $y, int $color): static
    {
        $this->buffer->setPixel($x, $y, $color);

        return $this->record(__FUNCTION__);
    }

    public function drawPixels(array $pixels): static
    {
        foreach ($pixels as [$x, $y, $color]) {
            $this->buffer->setPixel($x, $y, $color);
        }

        return $this->record(__FUNCTION__);
    }

    public function drawLine(int $x0, int $y0, int $x1, int $y1, int $color): static
    {
        return $this->record(__FUNCTION__);
    }

    public function drawHorizontalLine(int $x, int $y, int $w, int $color): static
    {
        $this->buffer->setSegment($x, $y, $w, 1, $color);

        return $this->record(__FUNCTION__);
    }

    public function drawVerticalLine(int $x, int $y, int $h, int $color): static
    {
        $this->buffer->setSegment($x, $y, 1, $h, $color);

        return $this->record(__FUNCTION__);
    }

    public function drawLines(array $lines): static
    {
        return $this->record(__FUNCTION__);
    }

    public function drawRect(int $x, int $y, int $w, int $h, int $color): static
    {
        return $this->record(__FUNCTION__);
    }

    public function fillRect(int $x, int $y, int $w, int $h, int $color): static
    {
        $this->buffer->setSegment($x, $y, $w, $h, $color);

        return $this->record(__FUNCTION__);
    }

    public function drawRoundRect(int $x, int $y, int $w, int $h, int $r, int $color): static
    {
        return $this->record(__FUNCTION__);
    }

    public function fillRoundRect(int $x, int $y, int $w, int $h, int $r, int $color): static
    {
        return $this->record(__FUNCTION__);
    }

    public function drawCircle(int $x0, int $y0, int $r, int $color): static
    {
        return $this->record(__FUNCTION__);
    }

    public function fillCircle(int $x0, int $y0, int $r, int $color): static
    {
        return $this->record(__FUNCTION__);
    }

    public function drawEllipse(int $x0, int $y0, int $rw, int $rh, int $color): static
    {
        return $this->record(__FUNCTION__);
    }

    public function fillEllipse(int $x0, int $y0, int $rw, int $rh, int $color): static
    {
        return $this->record(__FUNCTION__);
    }

    public function drawTriangle(int $x0, int $y0, int $x1, int $y1, int $x2, int $y2, int $color): static
    {
        return $this->record(__FUNCTION__);
    }

    public function fillTriangle(int $x0, int $y0, int $x1, int $y1, int $x2, int $y2, int $color): static
    {
        return $this->record(__FUNCTION__);
    }

    public function fill(int $color): static
    {
        $this->buffer->setSegment(0, 0, $this->buffer->viewportWidth(), $this->buffer->viewportHeight(), $color);

        return $this->record(__FUNCTION__);
    }

    public function setCursor(int $x, int $y): static
    {
        return $this->record(__FUNCTION__);
    }

    public function setTextSize(int $s, ?int $y = null): static
    {
        return $this->record(__FUNCTION__);
    }

    public function setTextColor(int $color, ?int $bg = null): static
    {
        return $this->record(__FUNCTION__);
    }

    public function setTextWrap(bool $wrap): static
    {
        return $this->record(__FUNCTION__);
    }

    public function setCp437(bool $enable): static
    {
        return $this->record(__FUNCTION__);
    }

    public function write(int $c): static
    {
        return $this->record(__FUNCTION__);
    }

    public function drawChar(int $x, int $y, int $c, int $color, int $bg, int $size_x, int $size_y): static
    {
        return $this->record(__FUNCTION__);
    }

    public function print(string $str): static
    {
        return $this->record(__FUNCTION__);
    }

    public function println(string $str = ''): static
    {
        return $this->record(__FUNCTION__);
    }

    public function getTextBounds(string $str, int $x, int $y): array
    {
        $this->calls[] = __FUNCTION__;

        return ['x1' => $x, 'y1' => $y, 'w' => 0, 'h' => 0];
    }

    public function buffer(): Framebuffer
    {
        return $this->buffer;
    }

    /**
     * @return array<int, DumpedBuffer>
     */
    public function render(): array
    {
        return $this->buffer->dump();
    }

    public static function preferredFramebuffer(FormatSpec $format_spec, int $width, int $height): Framebuffer
    {
        return match ($format_spec->pixel_format) {
            PixelFormat::MONO_VERTICAL_PAGE => new PageSegmentBuffer($width, $height, $format_spec),
            PixelFormat::ROW_MAJOR => new DirtyRegionsBuffer($width, $height, $format_spec),
            default => new FullFramebuffer($width, $height, $format_spec),
        };
    }
}
