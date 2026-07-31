<?php

namespace DeptOfScrapyardRobotics\Tests\Framebuffers;

use Fabricate\Contracts\Framebuffers\Enums\BitDepth;
use Fabricate\Contracts\Framebuffers\Enums\PixelFormat;
use Fabricate\Contracts\Framebuffers\Enums\RenderType;
use Fabricate\Framebuffers\DataObjects\DumpedBuffer;
use Fabricate\Framebuffers\FormatSpec;
use Fabricate\Framebuffers\Strategy\DirtyRegionsBuffer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DirtyRegionsBufferTest extends TestCase
{
    public function testACleanBufferEmitsNothing(): void
    {
        $this->assertSame([], (new DirtyRegionsBuffer(4, 4, $this->spec()))->dump());
    }

    public function testASinglePixelEmitsAOnePixelPartialUpdate(): void
    {
        $buffer = new DirtyRegionsBuffer(4, 4, $this->spec());
        $buffer->setPixel(1, 2, 0xAB);

        $updates = $buffer->dump();

        $this->assertCount(1, $updates);
        $this->assertSame(RenderType::PARTIAL, $updates[0]->render_type);
        $this->assertSame([1, 2, 1, 1], $this->extent($updates[0]));
        $this->assertSame([0xAB], $updates[0]->raw_data);
    }

    public function testASegmentEmitsOneRegionWithGoldenBytes(): void
    {
        $buffer = new DirtyRegionsBuffer(4, 4, $this->spec());
        $buffer->setSegment(1, 1, 2, 2, 0x0C);

        $updates = $buffer->dump();

        $this->assertCount(1, $updates);
        $this->assertSame([1, 1, 2, 2], $this->extent($updates[0]));
        $this->assertSame([0x0C, 0x0C, 0x0C, 0x0C], $updates[0]->raw_data);
    }

    public function testDistantPixelsStayAsSeparateRegions(): void
    {
        $buffer = new DirtyRegionsBuffer(4, 4, $this->spec());
        $buffer->setPixel(0, 0, 1)->setPixel(3, 3, 1);

        $this->assertSame(
            [[0, 0, 1, 1], [3, 3, 1, 1]],
            $this->sortedExtents($buffer->dump()),
        );
    }

    /**
     * Coalescing is what keeps a frame from turning into dozens of tiny
     * transfers, each carrying its own address-window overhead.
     */
    public function testAdjacentPixelsCoalesceIntoOneRegion(): void
    {
        $buffer = new DirtyRegionsBuffer(4, 4, $this->spec());
        $buffer->setPixel(0, 0, 1)->setPixel(1, 0, 1);

        $this->assertSame([[0, 0, 2, 1]], $this->sortedExtents($buffer->dump()));
    }

    /**
     * A single pixel landing between two islands touches both, so the merge has
     * to keep folding until the new region stands alone.
     */
    public function testAPixelBridgingTwoIslandsCollapsesAllThree(): void
    {
        $islands = new DirtyRegionsBuffer(8, 4, $this->spec());
        $islands->setPixel(0, 0, 1)->setPixel(2, 0, 1);

        $this->assertCount(2, $this->sortedExtents($islands->dump()));

        $bridged = new DirtyRegionsBuffer(8, 4, $this->spec());
        $bridged->setPixel(0, 0, 1)->setPixel(2, 0, 1)->setPixel(1, 0, 1);

        $this->assertSame([[0, 0, 3, 1]], $this->sortedExtents($bridged->dump()));
    }

    public function testDamageIsClippedToTheSurface(): void
    {
        $buffer = new DirtyRegionsBuffer(4, 4, $this->spec());
        $buffer->setSegment(-2, -2, 8, 8, 1);

        $this->assertSame([[0, 0, 4, 4]], $this->sortedExtents($buffer->dump()));
    }

    public function testPixelsEntirelyOffSurfaceRecordNoDamage(): void
    {
        $buffer = new DirtyRegionsBuffer(4, 4, $this->spec());
        $buffer->setPixel(-1, 0, 1)->setPixel(4, 4, 1);

        $this->assertSame([], $buffer->dump());
    }

    public function testMarkAllDirtyEmitsOneFullSurfaceRegion(): void
    {
        $buffer = new DirtyRegionsBuffer(4, 2, $this->spec());
        $updates = $buffer->markAllDirty()->dump();

        $this->assertCount(1, $updates);
        $this->assertSame([0, 0, 4, 2], $this->extent($updates[0]));
        $this->assertCount(8, $updates[0]->raw_data);
    }

    public function testDumpingResetsTheDamageSet(): void
    {
        $buffer = new DirtyRegionsBuffer(4, 4, $this->spec());
        $buffer->setPixel(1, 1, 1);

        $this->assertCount(1, $buffer->dump());
        $this->assertSame([], $buffer->dump());
    }

    /**
     * Partial refresh presupposes a retained canvas: the regions that were not
     * transmitted must still be valid on the panel, so the buffer has to keep
     * believing in the pixels it already sent.
     */
    public function testFlushKeepsTheCanvasSoLaterFramesRefreshOnlyWhatChanged(): void
    {
        $buffer = new DirtyRegionsBuffer(4, 2, $this->spec());
        $buffer->setPixel(1, 0, 0xAB);
        $buffer->flush();

        $this->assertSame(0xAB, $buffer->getPixel(1, 0));

        $updates = $buffer->markAllDirty()->dump();

        $this->assertSame([0x00, 0xAB, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00], $updates[0]->raw_data);
    }

    public function testFlushAndDumpAgreeForAPartialUpdateBuffer(): void
    {
        $flushed = new DirtyRegionsBuffer(4, 4, $this->spec());
        $dumped = new DirtyRegionsBuffer(4, 4, $this->spec());

        $flushed->setSegment(1, 1, 2, 2, 0x22);
        $dumped->setSegment(1, 1, 2, 2, 0x22);

        $this->assertSame(
            $this->sortedExtents($dumped->dump()),
            $this->sortedExtents($flushed->flush()),
        );
    }

    public function testASecondFlushWithNoDrawingTransmitsNothing(): void
    {
        $buffer = new DirtyRegionsBuffer(4, 4, $this->spec());
        $buffer->setPixel(2, 2, 1);

        $this->assertCount(1, $buffer->flush());
        $this->assertSame([], $buffer->flush());
    }

    public function testItRefusesToPackNonRowMajorSurfaces(): void
    {
        $buffer = new DirtyRegionsBuffer(8, 8, new FormatSpec(PixelFormat::MONO_VERTICAL_PAGE, BitDepth::B1));
        $buffer->setPixel(0, 0, 1);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DirtyRegionsBuffer only packs ROW_MAJOR surfaces');

        $buffer->dump();
    }

    /**
     * @param  array<int, DumpedBuffer>  $updates
     * @return array<int, array{0: int, 1: int, 2: int, 3: int}>
     */
    protected function sortedExtents(array $updates): array
    {
        $extents = array_map(fn (DumpedBuffer $update) => $this->extent($update), $updates);

        // The dirty set is an unordered bag, so compare as a sorted set rather
        // than depending on merge order.
        usort($extents, fn (array $a, array $b) => [$a[1], $a[0]] <=> [$b[1], $b[0]]);

        return $extents;
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
        return new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B8);
    }
}
