<?php

namespace RealityInterface\Displays\Applied\ePaper\Buffers;

use InvalidArgumentException;
use ScrapyardIO\NutsAndBolts\Buffers\FormatSpecFrameBuffer;
use ScrapyardIO\NutsAndBolts\DataObjects\ChannelSpec;
use ScrapyardIO\NutsAndBolts\DataObjects\DumpedBuffer;
use ScrapyardIO\NutsAndBolts\DataObjects\FormatSpec;
use ScrapyardIO\NutsAndBolts\DataObjects\PixelGrid;
use ScrapyardIO\NutsAndBolts\Enums\BitOrder;
use ScrapyardIO\NutsAndBolts\Enums\RenderType;

/**
 * A multi-plane drawing surface for channel-sorted (ePaper) panels.
 *
 * Where a single-plane buffer stores one logical value per pixel, this buffer
 * keeps one 1bpp {@see PixelGrid} per colour channel described by its
 * {@see FormatSpec} palette. A write "sorts" the drawn colour into the planes:
 * the plane that owns the colour gets its bit set, every other plane is
 * cleared, and a colour with no plane (e.g. white) clears them all.
 *
 * {@see dump()} packs each plane to 1bpp horizontal bytes and emits them keyed
 * by colour. Planes that never received their colour are omitted, so a panel
 * only drives the RAMs that actually carry content.
 */
class ChannelSortedFrameBuffer extends FormatSpecFrameBuffer
{
    /**
     * @var array<int, PixelGrid>  keyed by channel colour int
     */
    protected array $planes = [];

    /**
     * @var array<int, ChannelSpec>  keyed by channel colour int
     */
    protected array $channel_specs = [];

    public function __construct(int $width, int $height, FormatSpec $format_spec)
    {
        parent::__construct($width, $height, $format_spec);

        $palette = $format_spec->palette;
        if (is_null($palette)) {
            throw new InvalidArgumentException('ChannelSortedFrameBuffer requires a FormatSpec with a channel palette.');
        }

        foreach ($palette->channels as $channel) {
            $this->planes[$channel->color] = new PixelGrid($width, $height, 0);
            $this->channel_specs[$channel->color] = $channel;
        }
    }

    public function setPixel(int $x, int $y, int $value): static
    {
        foreach ($this->planes as $color => $plane) {
            if ($plane->contains($x, $y)) {
                $plane->set($x, $y, $value === $color ? 1 : 0);
            }
        }

        return $this;
    }

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
        $msb_first = $this->format_spec->bit_order !== BitOrder::LSB_FIRST;

        $channels = [];
        foreach ($this->planes as $color => $plane) {
            if ($this->planeHasContent($plane)) {
                $channels[$color] = $this->packMonoHorizontal(
                    $plane->toArray(),
                    $msb_first,
                    $this->channel_specs[$color]->inverted,
                );
            }
        }

        return [
            new DumpedBuffer(
                RenderType::FULL,
                $this->format_spec,
                $channels,
                width: $this->width,
                height: $this->height,
            ),
        ];
    }

    /**
     * @return array<int, DumpedBuffer>
     */
    public function flush(): array
    {
        $data = $this->dump();

        foreach ($this->planes as $plane) {
            $plane->clear();
        }

        return $data;
    }

    protected function planeHasContent(PixelGrid $plane): bool
    {
        foreach ($plane->values() as $value) {
            if ($value === 1) {
                return true;
            }
        }

        return false;
    }
}
