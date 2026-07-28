<?php

namespace Fabricate\Displays;

use Fabricate\Contracts\Displays\Display as DisplayContract;
use Fabricate\Contracts\Displays\Interfaces\PanelImplementation;
use Fabricate\Contracts\Framebuffers\Framebuffer;
use Fabricate\Contracts\Rendering\GFXRenderer;
use Fabricate\Framebuffers\FormatSpec;

abstract class Display implements DisplayContract
{
    public function __construct(
        protected readonly PanelImplementation $panel
    ) {}

    public function width(): int
    {
        return $this->panel->width();
    }

    public function height(): int
    {
        return $this->panel->height();
    }

    /**
     * The live spec — drivers can regenerate it at runtime (e.g. when the
     * memory addressing mode changes), so callers must never cache the result.
     */
    public function formatSpec(): FormatSpec
    {
        return $this->panel->formatSpec();
    }

    public function panel(): PanelImplementation
    {
        return $this->panel;
    }

    public function close(): void
    {
        $this->panel->close();
    }

    public function supportsRenderer(GFXRenderer $renderer): bool
    {
        return true;
    }

    public function supportsFramebuffer(Framebuffer $framebuffer): bool
    {
        return true;
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->panel->$name(...$arguments);
    }
}