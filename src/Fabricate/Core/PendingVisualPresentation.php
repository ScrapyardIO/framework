<?php

namespace Fabricate\Core;

use Fabricate\Contracts\Core\VisualException;
use Fabricate\Contracts\Displays\Display;
use Fabricate\Contracts\Framebuffers\Framebuffer;
use Fabricate\Framebuffers\FramebufferManager;
use Fabricate\Rendering\Renderer2D;
use Fabricate\Rendering\RenderManager;

class PendingVisualPresentation
{
    protected ?Renderer2D $renderer = null;

    protected ?Framebuffer $framebuffer = null;

    public function __construct(
        protected readonly Display $display,
        protected readonly RenderManager $renderers,
        protected readonly FramebufferManager $framebuffers,
    ) {}

    public function renderer(string $name): static
    {
        $renderer = $this->renderers->freshEngine($name)->renderer;

        if (! $renderer instanceof Renderer2D) {
            throw VisualException::invalidRenderer($renderer::class);
        }

        $this->renderer = $renderer;

        return $this;
    }

    public function buffer(string $name): static
    {
        $this->framebuffer = $this->framebuffers->make(
            $name,
            $this->display->width(),
            $this->display->height(),
            $this->display->formatSpec(),
        );

        return $this;
    }

    public function present(): VisualPresentation
    {
        $renderer = $this->renderer
            ?? throw VisualException::missingComponent('renderer');
        $framebuffer = $this->framebuffer
            ?? throw VisualException::missingComponent('framebuffer');

        // Windowed SDL3 (and similar) can rebind a headless buffer onto the
        // panel's live GPU renderer so present() skips full-frame readback.
        if (method_exists($framebuffer, 'bindDisplaySurface')) {
            $bound = $framebuffer->bindDisplaySurface($this->display);

            if ($bound instanceof Framebuffer) {
                $framebuffer = $bound;
                $this->framebuffer = $bound;
            }
        }

        $this->validateCompatibility($renderer, $framebuffer);
        $renderer->useFramebuffer($framebuffer);

        return new VisualPresentation($this->display, $framebuffer, $renderer);
    }

    protected function validateCompatibility(Renderer2D $renderer, Framebuffer $framebuffer): void
    {
        $pairs = [
            [$this->display->supportsRenderer($renderer), $this->display, $renderer],
            [$renderer->supportsDisplay($this->display), $renderer, $this->display],
            [$this->display->supportsFramebuffer($framebuffer), $this->display, $framebuffer],
            [$framebuffer->supportsDisplay($this->display), $framebuffer, $this->display],
            [$renderer->supportsFramebuffer($framebuffer), $renderer, $framebuffer],
            [$framebuffer->supportsRenderer($renderer), $framebuffer, $renderer],
        ];

        foreach ($pairs as [$compatible, $left, $right]) {
            if (! $compatible) {
                throw VisualException::incompatible($left::class, $right::class);
            }
        }
    }
}
