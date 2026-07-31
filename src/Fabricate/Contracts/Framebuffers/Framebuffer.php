<?php

namespace Fabricate\Contracts\Framebuffers;

use Fabricate\Contracts\Displays\Display;
use Fabricate\Contracts\Rendering\GFXRenderer;
use Fabricate\Framebuffers\DataObjects\DamageGranularity;

interface Framebuffer
{
    public function viewportWidth(): int;
    public function viewportHeight(): int;
    public function getPixel(int $x, int $y): int;
    public function setPixels(array $pixels): static;
    public function setPixel(int $x, int $y, int $value): static;
    public function setRegion(array $coordinates, int $value): static;
    public function setSegment(int $x, int $y, int $width, int $height, int $color): static;
    public function blitTo(Framebuffer $target, int $offset_x = 0, int $offset_y = 0): Framebuffer;
    public function blitFrom(Framebuffer $source, int $offset_x = 0, int $offset_y = 0): Framebuffer;

    public function dump(): array;

    public function flush(): array;

    /**
     * The smallest region this surface can usefully transmit, so callers can
     * snap damage to real transmit units rather than guessing.
     */
    public function damageGranularity(): DamageGranularity;

    /**
     * True when the logical canvas still holds the previous frame after a
     * present, which is what makes retained partial repaint possible.
     *
     * False for surfaces that reset on flush and for windowed SDL, where the
     * backbuffer is undefined once presented. Callers seeing false must repaint
     * everything each frame.
     */
    public function preservesContentsOnPresent(): bool;

    public function supportsDisplay(Display $display): bool;

    public function supportsRenderer(GFXRenderer $renderer): bool;
}
