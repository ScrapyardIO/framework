<?php

namespace BareMetal\Contracts\Framebuffers;

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
}
