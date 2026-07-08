<?php

namespace BareMetal\Framebuffers;

use BareMetal\Contracts\Framebuffers\DTO\DumpedBuffer;
use BareMetal\Contracts\Framebuffers\Enums\RenderType;

class PageSegmentBuffer extends FormatSpecFramebuffer
{
    protected static string $factory_class = PageSegmentBufferFactory::class;

    /** Rows per page: a vertical-page byte stacks 8 rows. */
    protected int $page_height = 8;

    /**
     * Pages touched since the last dump, keyed by page index.
     *
     * @var array<int, true>
     */
    protected array $dirty_pages = [];

    public function setPixel(int $x, int $y, int $value): static
    {
        if ($this->grid->contains($x, $y)) {
            $this->grid->set($x, $y, $value);
            $this->dirty_pages[intdiv($y, $this->page_height)] = true;
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
     * Force every page to be re-emitted on the next dump (a full repaint).
     */
    public function markAllDirty(): static
    {
        $pages = intdiv($this->height + ($this->page_height - 1), $this->page_height);

        for ($page = 0; $page < $pages; $page++) {
            $this->dirty_pages[$page] = true;
        }

        return $this;
    }

    /**
     * @return array<int, DumpedBuffer>
     */
    public function dump(): array
    {
        if ($this->dirty_pages === []) {
            return [];
        }

        // Pack the whole canvas once (page-major vertical-page bytes), then hand
        // out only the dirty pages' slices. Page p owns bytes [p*width, (p+1)*width).
        $packed = $this->formatRawDump();
        $bytes_per_page = $this->width;

        $pages = array_keys($this->dirty_pages);
        sort($pages);

        $updates = [];

        foreach ($pages as $page) {
            $origin_y = $page * $this->page_height;

            $updates[] = new DumpedBuffer(
                RenderType::PARTIAL,
                $this->format_spec,
                array_slice($packed, $page * $bytes_per_page, $bytes_per_page),
                origin_x: 0,
                origin_y: $origin_y,
                width: $this->width,
                height: min($this->page_height, $this->height - $origin_y),
            );
        }

        $this->dirty_pages = [];

        return $updates;
    }

    /**
     * @return array<int, DumpedBuffer>
     */
    public function flush(): array
    {
        $data = $this->dump();

        $this->grid->clear();
        $this->dirty_pages = [];

        return $data;
    }
}
