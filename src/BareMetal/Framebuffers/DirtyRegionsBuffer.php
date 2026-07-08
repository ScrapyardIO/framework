<?php

namespace BareMetal\Framebuffers;

use BareMetal\Contracts\Framebuffers\DTO\DumpedBuffer;
use BareMetal\Contracts\Framebuffers\Enums\Endianness;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Contracts\Framebuffers\Enums\RenderType;
use RuntimeException;

class DirtyRegionsBuffer extends FormatSpecFramebuffer
{
    protected static string $factory_class = DirtyRegionsBufferFactory::class;

    /**
     * Coalesced dirty rectangles as inclusive [left, top, right, bottom] bounds.
     *
     * @var array<int, array{0: int, 1: int, 2: int, 3: int}>
     */
    protected array $dirty_regions = [];

    public function setPixel(int $x, int $y, int $value): static
    {
        if ($this->grid->contains($x, $y)) {
            $this->grid->set($x, $y, $value);
            $this->markDirty($x, $y, $x, $y);
        }

        return $this;
    }

    public function setSegment(int $x, int $y, int $width, int $height, int $color): static
    {
        for ($row = 0; $row < $height; $row++) {
            for ($col = 0; $col < $width; $col++) {
                if ($this->grid->contains($x + $col, $y + $row)) {
                    $this->grid->set($x + $col, $y + $row, $color);
                }
            }
        }

        $this->markDirty($x, $y, ($x + $width) - 1, ($y + $height) - 1);

        return $this;
    }

    /**
     * Force the whole surface to be re-emitted as one region on the next dump.
     */
    public function markAllDirty(): static
    {
        $this->dirty_regions = [[0, 0, $this->width - 1, $this->height - 1]];

        return $this;
    }

    /**
     * @return array<int, DumpedBuffer>
     */
    public function dump(): array
    {
        if ($this->dirty_regions === []) {
            return [];
        }

        $this->guardRowMajor();

        $updates = [];

        foreach ($this->dirty_regions as [$left, $top, $right, $bottom]) {
            $width = ($right - $left) + 1;
            $height = ($bottom - $top) + 1;

            $updates[] = new DumpedBuffer(
                RenderType::PARTIAL,
                $this->format_spec,
                $this->packRegion($left, $top, $width, $height),
                origin_x: $left,
                origin_y: $top,
                width: $width,
                height: $height,
            );
        }

        $this->dirty_regions = [];

        return $updates;
    }

    /**
     * @return array<int, DumpedBuffer>
     */
    public function flush(): array
    {
        $data = $this->dump();

        $this->grid->clear();
        $this->dirty_regions = [];

        return $data;
    }

    /**
     * Clip a rectangle to the surface, then merge it into the dirty set,
     * unioning with every region it overlaps or touches until it stands alone.
     */
    protected function markDirty(int $left, int $top, int $right, int $bottom): void
    {
        $left = max(0, $left);
        $top = max(0, $top);
        $right = min($this->width - 1, $right);
        $bottom = min($this->height - 1, $bottom);

        if (($left > $right) || ($top > $bottom)) {
            return;
        }

        $merged = true;

        while ($merged) {
            $merged = false;

            foreach ($this->dirty_regions as $index => [$region_left, $region_top, $region_right, $region_bottom]) {
                $touches = ($left <= $region_right + 1) && ($region_left <= $right + 1)
                    && ($top <= $region_bottom + 1) && ($region_top <= $bottom + 1);

                if ($touches) {
                    $left = min($left, $region_left);
                    $top = min($top, $region_top);
                    $right = max($right, $region_right);
                    $bottom = max($bottom, $region_bottom);

                    unset($this->dirty_regions[$index]);
                    $merged = true;
                }
            }
        }

        $this->dirty_regions[] = [$left, $top, $right, $bottom];
    }

    /**
     * Slice a rectangle of the canvas into a flat, row-major byte stream, each
     * pixel word split into bit_depth-many bytes in endianness order.
     *
     * @return array<int, int>
     */
    protected function packRegion(int $x, int $y, int $width, int $height): array
    {
        $bytes_per_pixel = intdiv($this->format_spec->bit_depth->value + 7, 8);
        $msb_first = ($this->format_spec->endianness !== Endianness::LSB);

        $bytes = [];

        for ($row = $y; $row < $y + $height; $row++) {
            for ($col = $x; $col < $x + $width; $col++) {
                $pixel = $this->grid->get($col, $row);

                for ($i = 0; $i < $bytes_per_pixel; $i++) {
                    $shift = $msb_first ? (($bytes_per_pixel - 1 - $i) * 8) : ($i * 8);
                    $bytes[] = ($pixel >> $shift) & 0xFF;
                }
            }
        }

        return $bytes;
    }

    protected function guardRowMajor(): void
    {
        if ($this->format_spec->pixel_format !== PixelFormat::ROW_MAJOR) {
            throw new RuntimeException(
                "DirtyRegionsBuffer only packs ROW_MAJOR surfaces, got {$this->format_spec->pixel_format->value}."
            );
        }
    }
}
