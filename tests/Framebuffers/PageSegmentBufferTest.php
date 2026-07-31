<?php

namespace DeptOfScrapyardRobotics\Tests\Framebuffers;

use Fabricate\Contracts\Framebuffers\Enums\BitDepth;
use Fabricate\Contracts\Framebuffers\Enums\PixelFormat;
use Fabricate\Contracts\Framebuffers\Enums\RenderType;
use Fabricate\Framebuffers\DataObjects\DumpedBuffer;
use Fabricate\Framebuffers\FormatSpec;
use Fabricate\Framebuffers\Strategy\PageSegmentBuffer;
use PHPUnit\Framework\TestCase;

/**
 * Vertical-page monochrome, the SSD1306/SH1106 layout: one byte stacks 8 rows,
 * bytes emitted page-major, and with no explicit bit order the top row lands on
 * bit 0.
 */
class PageSegmentBufferTest extends TestCase
{
    public function testACleanBufferEmitsNothing(): void
    {
        $this->assertSame([], (new PageSegmentBuffer(8, 16, $this->spec()))->dump());
    }

    public function testASinglePixelEmitsItsPageAsAPartialUpdate(): void
    {
        $buffer = new PageSegmentBuffer(8, 16, $this->spec());
        $buffer->setPixel(0, 0, 1);

        $updates = $buffer->dump();

        $this->assertCount(1, $updates);
        $this->assertSame(RenderType::PARTIAL, $updates[0]->render_type);
        $this->assertSame([0, 0, 8, 8], $this->extent($updates[0]));
        $this->assertSame([0x01, 0, 0, 0, 0, 0, 0, 0], $updates[0]->raw_data);
    }

    public function testStackedRowsPackIntoTheBitsOfOneByte(): void
    {
        $buffer = new PageSegmentBuffer(8, 8, $this->spec());
        $buffer->setPixel(0, 0, 1)->setPixel(0, 1, 1)->setPixel(0, 7, 1);

        // Rows 0, 1 and 7 of column 0 → bits 0, 1 and 7.
        $this->assertSame([0x83, 0, 0, 0, 0, 0, 0, 0], $buffer->dump()[0]->raw_data);
    }

    public function testDamageInALaterPageReportsThatPagesOrigin(): void
    {
        $buffer = new PageSegmentBuffer(8, 16, $this->spec());
        $buffer->setPixel(3, 9, 1);

        $updates = $buffer->dump();

        $this->assertCount(1, $updates);
        $this->assertSame([0, 8, 8, 8], $this->extent($updates[0]));
        $this->assertSame([0, 0, 0, 0x02, 0, 0, 0, 0], $updates[0]->raw_data);
    }

    /**
     * Contiguous pages become one transfer, which is what cuts address-window
     * overhead on buses like FTDI.
     */
    public function testContiguousDirtyPagesCoalesceIntoOneTransfer(): void
    {
        $buffer = new PageSegmentBuffer(8, 32, $this->spec());
        $buffer->setPixel(0, 0, 1)->setPixel(0, 8, 1);

        $updates = $buffer->dump();

        $this->assertCount(1, $updates);
        $this->assertSame([0, 0, 8, 16], $this->extent($updates[0]));
        $this->assertCount(16, $updates[0]->raw_data);
    }

    public function testNonContiguousDirtyPagesStayAsSeparateTransfers(): void
    {
        $buffer = new PageSegmentBuffer(8, 32, $this->spec());
        $buffer->setPixel(0, 0, 1)->setPixel(0, 16, 1);

        $updates = $buffer->dump();

        $this->assertCount(2, $updates);
        $this->assertSame([0, 0, 8, 8], $this->extent($updates[0]));
        $this->assertSame([0, 16, 8, 8], $this->extent($updates[1]));
    }

    public function testATrailingPartialPageIsClampedToTheSurface(): void
    {
        // 12 rows is a page and a half, so the second page is only 4 rows tall.
        $buffer = new PageSegmentBuffer(8, 12, $this->spec());
        $buffer->setPixel(0, 10, 1);

        $updates = $buffer->dump();

        $this->assertSame([0, 8, 8, 4], $this->extent($updates[0]));
    }

    public function testASegmentMarksEveryPageItSpans(): void
    {
        $buffer = new PageSegmentBuffer(8, 32, $this->spec());
        $buffer->setSegment(0, 6, 2, 4, 1);

        $updates = $buffer->dump();

        // Rows 6..9 straddle pages 0 and 1, which then coalesce.
        $this->assertCount(1, $updates);
        $this->assertSame([0, 0, 8, 16], $this->extent($updates[0]));
    }

    public function testMarkAllDirtyEmitsTheWholeSurfaceAsOneRun(): void
    {
        $buffer = new PageSegmentBuffer(8, 32, $this->spec());

        $updates = $buffer->markAllDirty()->dump();

        $this->assertCount(1, $updates);
        $this->assertSame([0, 0, 8, 32], $this->extent($updates[0]));
        $this->assertCount(32, $updates[0]->raw_data);
    }

    public function testDumpingResetsTheDirtyPages(): void
    {
        $buffer = new PageSegmentBuffer(8, 16, $this->spec());
        $buffer->setPixel(1, 1, 1);

        $this->assertCount(1, $buffer->dump());
        $this->assertSame([], $buffer->dump());
    }

    public function testPixelsOffSurfaceRecordNoDamage(): void
    {
        $buffer = new PageSegmentBuffer(8, 16, $this->spec());
        $buffer->setPixel(-1, 0, 1)->setPixel(8, 0, 1)->setPixel(0, 16, 1);

        $this->assertSame([], $buffer->dump());
    }

    /**
     * Required for partially refreshable ICs: the pages that were not sent must
     * remain valid on the panel, so the grid has to survive a present.
     */
    public function testFlushKeepsTheCanvasSoLaterFramesRefreshOnlyTouchedPages(): void
    {
        $buffer = new PageSegmentBuffer(8, 16, $this->spec());
        $buffer->setPixel(0, 0, 1);
        $buffer->flush();

        $this->assertSame(1, $buffer->getPixel(0, 0));

        $updates = $buffer->markAllDirty()->dump();

        $this->assertSame(0x01, $updates[0]->raw_data[0]);
    }

    public function testFlushAndDumpAgree(): void
    {
        $flushed = new PageSegmentBuffer(8, 16, $this->spec());
        $dumped = new PageSegmentBuffer(8, 16, $this->spec());

        $flushed->setPixel(2, 3, 1);
        $dumped->setPixel(2, 3, 1);

        $this->assertSame($dumped->dump()[0]->raw_data, $flushed->flush()[0]->raw_data);
    }

    public function testASecondFlushWithNoDrawingTransmitsNothing(): void
    {
        $buffer = new PageSegmentBuffer(8, 16, $this->spec());
        $buffer->setPixel(4, 4, 1);

        $this->assertCount(1, $buffer->flush());
        $this->assertSame([], $buffer->flush());
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: int}
     */
    protected function extent(DumpedBuffer $update): array
    {
        return [$update->origin_x, $update->origin_y, $update->width, $update->height];
    }

    protected function spec(): FormatSpec
    {
        return new FormatSpec(PixelFormat::MONO_VERTICAL_PAGE, BitDepth::B1);
    }
}
