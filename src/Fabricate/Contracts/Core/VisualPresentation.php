<?php

namespace Fabricate\Contracts\Core;

use Fabricate\Contracts\Displays\Display;
use Fabricate\Contracts\Framebuffers\Framebuffer;
use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\Framebuffers\FormatSpec;
use Fabricate\Rendering\Renderer2D;

interface VisualPresentation extends DrawingSurface
{
    public function width(): int;

    public function height(): int;

    public function formatSpec(): FormatSpec;

    public function clear(int $color = 0): static;

    public function present(): static;

    public function close(): static;

    /**
     * True when a windowed display has been asked to close (chrome X / quit).
     * Embedded / console presentations always return false.
     */
    public function shouldClose(): bool;

    public function getDisplay(): Display;

    public function getFramebuffer(): Framebuffer;

    public function getRenderer(): Renderer2D;
}
