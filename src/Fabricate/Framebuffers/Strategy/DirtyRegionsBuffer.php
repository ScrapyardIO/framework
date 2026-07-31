<?php

namespace Fabricate\Framebuffers\Strategy;

use Fabricate\Contracts\Framebuffers\Enums\PixelFormat;
use Fabricate\Contracts\Framebuffers\Enums\RenderType;
use Fabricate\Framebuffers\DataObjects\DamageGranularity;
use Fabricate\Framebuffers\DataObjects\DumpedBuffer;
use Fabricate\Framebuffers\Factory\DirtyRegionsBufferFactory;
use Fabricate\Framebuffers\Packers\RowMajorPacker;
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
     * Emit dirty regions and keep the logical grid intact so subsequent draws
     * can refresh only the regions they touch.
     *
     * Clearing the grid here would leave the buffer believing the canvas is
     * blank while the panel still shows the last transmitted image, with no
     * recorded damage to reconcile the two — incompatible with the PARTIAL
     * updates this strategy emits, since partial refresh presupposes a retained
     * canvas. {@see PageSegmentBuffer::flush()} keeps its grid for the same
     * reason. Callers wanting a blank surface clear it explicitly, which records
     * the damage needed to actually transmit the blank.
     *
     * @return array<int, DumpedBuffer>
     */
    public function flush(): array
    {
        return $this->dump();
    }

    /**
     * Individual pixels are tracked, so damage needs no quantizing.
     */
    public function damageGranularity(): DamageGranularity
    {
        return DamageGranularity::pixel($this->width, $this->height);
    }

    /**
     * {@see flush()} keeps the grid, which is what lets successive frames
     * refresh only the regions they touch.
     */
    public function preservesContentsOnPresent(): bool
    {
        return true;
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
     * Slice a rectangle of the canvas into a flat, row-major byte stream using
     * the shared {@see RowMajorPacker} (includes ST77xx COLOR12 pair packing).
     *
     * @return array<int, int>
     */
    protected function packRegion(int $x, int $y, int $width, int $height): array
    {
        $grid = [];

        for ($row = 0; $row < $height; $row++) {
            for ($col = 0; $col < $width; $col++) {
                $grid[$row][$col] = $this->grid->get($x + $col, $y + $row);
            }
        }

        return (new RowMajorPacker)->pack($grid, $this->format_spec, $width, $height);
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
