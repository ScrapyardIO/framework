<?php

namespace Fabricate\Core;

use Fabricate\Contracts\Core\VisualException;
use Fabricate\Contracts\Core\VisualPresentation as PresentationContract;
use Fabricate\Contracts\Displays\Display as DisplayInterface;
use Fabricate\Contracts\Displays\WindowedDisplay as WindowedDisplayContract;
use Fabricate\Contracts\Framebuffers\Enums\BitDepth;
use Fabricate\Contracts\Framebuffers\Framebuffer as FramebufferInterface;
use Fabricate\Displays\Display as BaseDisplay;
use Fabricate\Framebuffers\FormatSpec;
use Fabricate\Rendering\Renderer2D;
use ReflectionMethod;

class VisualPresentation implements PresentationContract
{
    public function __construct(
        protected readonly DisplayInterface $display,
        protected readonly FramebufferInterface $framebuffer,
        protected readonly Renderer2D $renderer,
    ) {
        $this->renderer->setTextColor($this->defaultTextColor());
    }

    /**
     * Pick a lit / opaque default ink for the display's native pixel format.
     */
    protected function defaultTextColor(): int
    {
        return match ($this->display->formatSpec()->bit_depth) {
            BitDepth::B1 => 1,
            BitDepth::B12 => 0x0FFF,
            BitDepth::B16 => 0xFFFF,
            BitDepth::B18 => 0xFCFCFC,
            default => 0xFFFFFFFF,
        };
    }

    public function width(): int
    {
        return $this->display->width();
    }

    public function height(): int
    {
        return $this->display->height();
    }

    public function formatSpec(): FormatSpec
    {
        return $this->display->formatSpec();
    }

    public function drawPixel(int $x, int $y, int $color): static
    {
        $this->renderer->drawPixel($x, $y, $color);

        return $this;
    }

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

    public function drawHorizontalLine(int $x, int $y, int $w, int $color): static
    {
        $this->renderer->drawHorizontalLine($x, $y, $w, $color);

        return $this;
    }

    public function drawHLine(int $x, int $y, int $w, int $color): static
    {
        return $this->drawHorizontalLine($x, $y, $w, $color);
    }

    public function drawVerticalLine(int $x, int $y, int $h, int $color): static
    {
        $this->renderer->drawVerticalLine($x, $y, $h, $color);

        return $this;
    }

    public function drawVLine(int $x, int $y, int $h, int $color): static
    {
        return $this->drawVerticalLine($x, $y, $h, $color);
    }

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

    public function fillRect(int $x, int $y, int $w, int $h, int $color): static
    {
        $this->renderer->fillRect($x, $y, $w, $h, $color);

        return $this;
    }

    public function drawRoundRect(int $x, int $y, int $w, int $h, int $r, int $color): static
    {
        $this->renderer->drawRoundRect($x, $y, $w, $h, $r, $color);

        return $this;
    }

    public function fillRoundRect(int $x, int $y, int $w, int $h, int $r, int $color): static
    {
        $this->renderer->fillRoundRect($x, $y, $w, $h, $r, $color);

        return $this;
    }

    public function drawCircle(int $x0, int $y0, int $r, int $color): static
    {
        $this->renderer->drawCircle($x0, $y0, $r, $color);

        return $this;
    }

    public function fillCircle(int $x0, int $y0, int $r, int $color): static
    {
        $this->renderer->fillCircle($x0, $y0, $r, $color);

        return $this;
    }

    public function drawEllipse(int $x0, int $y0, int $rw, int $rh, int $color): static
    {
        $this->renderer->drawEllipse($x0, $y0, $rw, $rh, $color);

        return $this;
    }

    public function fillEllipse(int $x0, int $y0, int $rw, int $rh, int $color): static
    {
        $this->renderer->fillEllipse($x0, $y0, $rw, $rh, $color);

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

    public function fill(int $color): static
    {
        $this->renderer->fill($color);

        return $this;
    }

    public function clear(int $color = 0): static
    {
        return $this->fill($color);
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

    public function setFont(object|string|null $f = null): static
    {
        $this->renderer->setFont($f);

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

    public function getTextBounds(string $str, int $x, int $y): array
    {
        return $this->renderer->getTextBounds($str, $x, $y);
    }

    public function close(): static
    {
        $this->display->close();

        return $this;
    }

    public function shouldClose(): bool
    {
        if ($this->display instanceof WindowedDisplayContract) {
            return $this->display->shouldClose();
        }

        return false;
    }

    public function getDisplay(): DisplayInterface
    {
        return $this->display;
    }

    public function getFramebuffer(): FramebufferInterface
    {
        return $this->framebuffer;
    }

    public function getRenderer(): Renderer2D
    {
        return $this->renderer;
    }

    /**
     * @throws VisualException
     */
    public function displayCall(string $method, mixed ...$arguments): mixed
    {
        return $this->forwardCall('display', $this->display, $method, $arguments);
    }

    /**
     * @throws VisualException
     */
    public function framebufferCall(string $method, mixed ...$arguments): mixed
    {
        return $this->forwardCall('framebuffer', $this->framebuffer, $method, $arguments);
    }

    /**
     * @throws VisualException
     */
    public function rendererCall(string $method, mixed ...$arguments): mixed
    {
        return $this->forwardCall('renderer', $this->renderer, $method, $arguments);
    }

    public function present(): static
    {
        $frames = $this->framebuffer->flush();

        // Empty flush = framebuffer already presented natively (e.g. attached
        // SDL3 renderer). Still refresh window close state / event pump.
        if ($frames === []) {
            if ($this->display instanceof WindowedDisplayContract) {
                $this->display->shouldClose();
            }

            return $this;
        }

        foreach ($frames as $frame) {
            $this->display->flush($frame);
        }

        return $this;
    }

    /**
     * @throws VisualException
     */
    protected function forwardCall(
        string $component,
        object $target,
        string $method,
        array $arguments,
    ): mixed {
        $call_target = $target;

        if (! $this->hasPublicMethod($call_target, $method) && $target instanceof BaseDisplay) {
            $call_target = $target->panel();
        }

        if (! $this->hasPublicMethod($call_target, $method)) {
            throw VisualException::methodUnavailable($component, $method);
        }

        $result = $call_target->$method(...$arguments);

        return $result === $call_target ? $this : $result;
    }

    protected function hasPublicMethod(object $target, string $method): bool
    {
        return method_exists($target, $method)
            && new ReflectionMethod($target, $method)->isPublic();
    }
}