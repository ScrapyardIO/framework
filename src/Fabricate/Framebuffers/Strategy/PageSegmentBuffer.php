<?php

namespace Fabricate\Framebuffers\Strategy;

use Fabricate\Contracts\Framebuffers\Enums\RenderType;
use Fabricate\Framebuffers\DataObjects\DumpedBuffer;
use Fabricate\Framebuffers\Factory\PageSegmentBufferFactory;

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
        // out contiguous dirty page runs as single transfers. Coalescing cuts
        // MPSSE address-window overhead on buses like FTDI.
        $packed = $this->formatRawDump();
        $bytes_per_page = $this->width;

        $pages = array_keys($this->dirty_pages);
        sort($pages);

        $updates = [];

        foreach ($this->coalescePageRuns($pages) as [$startPage, $endPage]) {
            $pageCount = ($endPage - $startPage) + 1;
            $origin_y = $startPage * $this->page_height;
            $height = min($pageCount * $this->page_height, $this->height - $origin_y);

            $updates[] = new DumpedBuffer(
                RenderType::PARTIAL,
                $this->format_spec,
                array_slice($packed, $startPage * $bytes_per_page, $pageCount * $bytes_per_page),
                origin_x: 0,
                origin_y: $origin_y,
                width: $this->width,
                height: $height,
            );
        }

        $this->dirty_pages = [];

        return $updates;
    }

    /**
     * Emit dirty pages and keep the logical grid intact so subsequent draws can
     * refresh only the pages they touch (required for PartiallyRefreshable ICs).
     *
     * @return array<int, DumpedBuffer>
     */
    public function flush(): array
    {
        return $this->dump();
    }

    /**
     * Collapse sorted page indexes into inclusive [start, end] runs.
     *
     * @param  list<int>  $pages
     * @return list<array{0: int, 1: int}>
     */
    protected function coalescePageRuns(array $pages): array
    {
        if ($pages === []) {
            return [];
        }

        $runs = [];
        $start = $pages[0];
        $end = $pages[0];

        foreach (array_slice($pages, 1) as $page) {
            if ($page === ($end + 1)) {
                $end = $page;
                continue;
            }

            $runs[] = [$start, $end];
            $start = $page;
            $end = $page;
        }

        $runs[] = [$start, $end];

        return $runs;
    }
}
