<?php

namespace Fabricate\Framebuffers;

use Fabricate\Contracts\Framebuffers\DumpedBuffer;
use Fabricate\Contracts\Framebuffers\Enums\RenderType;
use Fabricate\Contracts\Framebuffers\FramebufferException;
use Fabricate\Framebuffers\Factory\FullFramebufferFactory;

/**
 * The simplest concrete buffer: it always emits its whole surface.
 *
 * No partial-refresh bookkeeping — every dump is a single FULL update covering
 * the entire grid from the origin, carrying the buffer's FormatSpec so a
 * downstream transcoder knows how the payload is shaped.
 */
final class FullFramebuffer extends FormatSpecFramebuffer
{
    protected static string $factory_class = FullFramebufferFactory::class;

    /**
     * @return array<int, DumpedBuffer>
     * @throws FramebufferException
     */
    public function dump(): array
    {
        return [
            new DumpedBuffer(
                RenderType::FULL,
                $this->format_spec,
                $this->formatRawDump(),
                width: $this->width,
                height: $this->height,
            ),
        ];
    }
}
