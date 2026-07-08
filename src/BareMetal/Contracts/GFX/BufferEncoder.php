<?php

namespace BareMetal\Contracts\GFX;

use BareMetal\Contracts\Framebuffers\DTO\DumpedBuffer;
use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;

/**
 * Converts a DumpedBuffer from one FormatSpec layout into another.
 *
 * Encoders are resolved by the BufferTranscoderService: each declares which
 * source → target conversions it can perform, so downstream packages can
 * register converters for formats the framework does not ship.
 */
interface BufferEncoder
{
    public function supports(FormatSpec $source, FormatSpec $target): bool;

    public function encode(DumpedBuffer $dump, FormatSpec $target): DumpedBuffer;
}
