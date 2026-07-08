<?php

namespace BareMetal\Contracts\Framebuffers;

interface PixelGrid
{
    public function values(): array;
    public function toArray(): array;
    public function fill(int $value): static;
    public function get(int $x, int $y): int;
    public function clear(int $value = 0): static;
    public function contains(int $x, int $y): bool;
    public function set(int $x, int $y, int $value): static;
}
