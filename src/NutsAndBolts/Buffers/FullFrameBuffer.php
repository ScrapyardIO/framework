<?php

namespace ScrapyardIO\NutsAndBolts\Buffers;

use ScrapyardIO\NutsAndBolts\Buffers\Factory\FullFrameBufferFactory;
use ScrapyardIO\NutsAndBolts\DataObjects\DumpedBuffer;
use ScrapyardIO\NutsAndBolts\Enums\RenderType;

/**
 * The simplest concrete buffer: it always emits its whole surface.
 *
 * No partial-refresh bookkeeping — every dump is a single FULL update covering
 * the entire grid from the origin, carrying the buffer's FormatSpec so a
 * downstream transcoder knows how the payload is shaped.
 */
final class FullFrameBuffer extends FormatSpecFrameBuffer
{
    protected static string $factory_class = FullFrameBufferFactory::class;

    /**
     * Fill a rectangular region with a single value.
     *
     * Off-surface cells are dropped by the inherited {@see setPixel()} clipping,
     * and non-positive dimensions write nothing.
     */
    public function setSegment(int $x, int $y, int $width, int $height, int $color): static
    {
        for ($row = 0; $row < $height; $row++) {
            for ($col = 0; $col < $width; $col++) {
                $this->setPixel($x + $col, $y + $row, $color);
            }
        }

        return $this;
    }

    /**
     * @return array<int, DumpedBuffer>
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
