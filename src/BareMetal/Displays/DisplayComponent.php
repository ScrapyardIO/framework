<?php

namespace BareMetal\Displays;

use BareMetal\Contracts\Displays\Display as DisplayInterface;
use BareMetal\Contracts\Displays\DisplayComponent as DisplayComponentContract;
use BareMetal\Contracts\Framebuffers\DTO\DumpedBuffer;
use BareMetal\Contracts\Framebuffers\Framebuffer as FramebufferInterface;
use BareMetal\Framebuffers\FormatSpecFramebuffer;
use BareMetal\GFX\Renderer2D;
use BareMetal\GFX\RenderingLibraries;
use BareMetal\GFX\Services\BufferTranscoderService;

/**
 * The end-developer facade: a display IC + framebuffer + renderer bundle.
 *
 * Construction resolution order:
 * - Renderer injected: it draws against its own buffer; since that buffer's
 *   spec may differ from the IC's, render()/flush() transcode each dump
 *   through {@see BufferTranscoderService} before transmit().
 * - No renderer: the default rendering library ({@see defaultRendererName()})
 *   is resolved via {@see RenderingLibraries} and built over the injected
 *   framebuffer — or, when none was given, over the renderer's preferred
 *   framebuffer carrying the IC's live FormatSpec, so the specs match and
 *   every dump takes the transcoder's no-op fast path.
 */
class DisplayComponent implements DisplayComponentContract
{
    protected Renderer2D $renderer;

    protected FramebufferInterface $framebuffer;

    public function __construct(
        protected readonly DisplayInterface $display,
        ?FramebufferInterface $framebuffer = null,
        ?Renderer2D $renderer = null,
    ) {
        if (is_null($renderer)) {
            /** @var class-string<Renderer2D> $renderer_class */
            $renderer_class = RenderingLibraries::resolve($this->defaultRendererName());

            $framebuffer ??= $renderer_class::preferredFramebuffer(
                $display->formatSpec(),
                $display->width(),
                $display->height()
            );

            $renderer = new $renderer_class($framebuffer);
        }

        $this->renderer = $renderer;

        // An injected renderer draws against its own buffer, so that buffer is
        // always the component's authoritative surface — a framebuffer passed
        // alongside a renderer would never be drawn into and is ignored.
        $this->framebuffer = $renderer->buffer();
    }

    /**
     * The rendering library a subclass reaches for when no renderer is
     * injected (e.g. windowed components default to sdl3).
     */
    protected function defaultRendererName(): string
    {
        return 'phpdafruit';
    }

    public function display(): DisplayInterface
    {
        return $this->display;
    }

    public function framebuffer(): FramebufferInterface
    {
        return $this->framebuffer;
    }

    public function renderer(): Renderer2D
    {
        return $this->renderer;
    }

    // -- Drawing API: proxies to the renderer, fluent on the component -------

    public function drawPixel(int $x, int $y, int $color): static
    {
        $this->renderer->drawPixel($x, $y, $color);

        return $this;
    }

    /**
     * @param  array<int, array{0: int, 1: int, 2: int}>  $pixels  Each entry is [x, y, color]
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

    public function drawHorizontalLine(int $x, int $y, int $w, int $color): static
    {
        $this->renderer->drawHorizontalLine($x, $y, $w, $color);

        return $this;
    }

    public function drawVerticalLine(int $x, int $y, int $h, int $color): static
    {
        $this->renderer->drawVerticalLine($x, $y, $h, $color);

        return $this;
    }

    /**
     * @param  array<int, array{0: int, 1: int, 2: int, 3: int, 4: int}>  $lines  Each entry is [x0, y0, x1, y1, color]
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

    // -- Render orchestration -------------------------------------------------

    /**
     * Dump the renderer's buffer(s), transcode when specs differ, and
     * transmit each frame to the display IC. Keeps drawn state intact.
     *
     * A full refresh (the default) re-emits the whole surface; passing true
     * lets partial-refresh buffers send only their dirty regions.
     */
    public function render(bool $partial_refresh = false): static
    {
        if (! $partial_refresh) {
            $this->markAllDirty();
        }

        return $this->transmitFrames($this->renderer->render());
    }

    /**
     * Like render(), but clears the drawn state after transmitting.
     */
    public function flush(): static
    {
        $this->markAllDirty();

        $buffer = $this->renderer->buffer();

        $frames = ($buffer instanceof FormatSpecFramebuffer)
            ? $buffer->flush()
            : $this->renderer->render();

        return $this->transmitFrames($frames);
    }

    /**
     * Partial-refresh buffers only dump what changed; a full refresh needs
     * them to re-emit everything. Buffers without dirty tracking (e.g.
     * FullFramebuffer) always dump their whole surface, so they have nothing
     * to mark.
     */
    protected function markAllDirty(): void
    {
        $buffer = $this->renderer->buffer();

        if (method_exists($buffer, 'markAllDirty')) {
            $buffer->markAllDirty();
        }
    }

    /**
     * Push frames out to the IC, transcoding any whose spec differs from the
     * display's. The transcoder is built per call against the IC's live
     * formatSpec() — drivers can regenerate their spec at runtime, so it is
     * compared per flush, never cached.
     *
     * @param  array<int, DumpedBuffer>  $frames
     */
    protected function transmitFrames(array $frames): static
    {
        $transcoder = new BufferTranscoderService($this->display->formatSpec());

        foreach ($frames as $frame) {
            $this->display->transmit($transcoder->transcode($frame));
        }

        return $this;
    }
}
