<?php

namespace Fabricate\Contracts\Displays;

use Fabricate\Contracts\Framebuffers\Framebuffer;
use Fabricate\Contracts\Rendering\GFXRenderer;
use Fabricate\Framebuffers\DataObjects\DumpedBuffer;
use Fabricate\Framebuffers\FormatSpec;

interface Display
{
    public function width(): int;

    public function height(): int;

    public function formatSpec(): FormatSpec;

    public function flush(DumpedBuffer $frame): void;

    public function close(): void;

    public function supportsRenderer(GFXRenderer $renderer): bool;

    public function supportsFramebuffer(Framebuffer $framebuffer): bool;
}