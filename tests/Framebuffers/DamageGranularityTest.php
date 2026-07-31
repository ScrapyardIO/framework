<?php

namespace DeptOfScrapyardRobotics\Tests\Framebuffers;

use Fabricate\Contracts\Framebuffers\Enums\BitDepth;
use Fabricate\Contracts\Framebuffers\Enums\Endianness;
use Fabricate\Contracts\Framebuffers\Enums\PixelFormat;
use Fabricate\Framebuffers\DataObjects\DamageGranularity;
use Fabricate\Framebuffers\FormatSpec;
use Fabricate\Framebuffers\Strategy\DirtyRegionsBuffer;
use Fabricate\Framebuffers\Strategy\FullFramebuffer;
use Fabricate\Framebuffers\Strategy\PageSegmentBuffer;
use Fabricate\NutsAndBolts\Geometry\Rect;
use PHPUnit\Framework\TestCase;

class DamageGranularityTest extends TestCase
{
    public function testPixelGranularityLeavesDamageUntouched(): void
    {
        $granularity = DamageGranularity::pixel(128, 64);
        $damage = new Rect(13, 27, 5, 3);

        $this->assertTrue($granularity->isPixelPerfect());
        $this->assertFalse($granularity->coversWholeSurface());
        $this->assertSame($damage->toBounds(), $granularity->snap($damage)->toBounds());
    }

    public function testRowGranularityGrowsDamageToWholePages(): void
    {
        $granularity = DamageGranularity::rows(8, 128, 64);

        // A 3px-tall smear inside page 1 costs the whole 128x8 page either way.
        $snapped = $granularity->snap(new Rect(40, 11, 6, 3));

        $this->assertSame([0, 8, 127, 15], $snapped->toBounds());
    }

    public function testRowGranularitySpansEveryPageDamageCrosses(): void
    {
        $granularity = DamageGranularity::rows(8, 128, 64);

        $snapped = $granularity->snap(new Rect(0, 7, 1, 2));

        $this->assertSame([0, 0, 127, 15], $snapped->toBounds());
    }

    public function testSnappingClampsAPartialTrailingBandToTheSurface(): void
    {
        // 60 rows do not divide into 8-row pages, so the last page is short.
        $granularity = DamageGranularity::rows(8, 128, 60);

        $snapped = $granularity->snap(new Rect(0, 58, 4, 2));

        $this->assertSame([0, 56, 127, 59], $snapped->toBounds());
    }

    public function testWholeSurfaceGranularityPromotesAnyDamageToEverything(): void
    {
        $granularity = DamageGranularity::wholeSurface(240, 240);

        $snapped = $granularity->snap(new Rect(100, 100, 2, 2));

        $this->assertTrue($granularity->coversWholeSurface());
        $this->assertFalse($granularity->isPixelPerfect());
        $this->assertSame([0, 0, 239, 239], $snapped->toBounds());
    }

    public function testSnappingClampsDamageOverhangingTheSurface(): void
    {
        $granularity = DamageGranularity::pixel(64, 32);

        $snapped = $granularity->snap(new Rect(60, 30, 20, 20));

        $this->assertSame([60, 30, 63, 31], $snapped->toBounds());
    }

    public function testDamageEntirelyOffSurfaceSnapsToEmpty(): void
    {
        $granularity = DamageGranularity::rows(8, 128, 64);

        $this->assertTrue($granularity->snap(new Rect(200, 200, 4, 4))->isEmpty());
        $this->assertTrue($granularity->snap(Rect::empty())->isEmpty());
    }

    public function testDirtyRegionsBufferReportsPixelGranularity(): void
    {
        $buffer = new DirtyRegionsBuffer(128, 64, $this->rowMajorSpec());

        $this->assertTrue($buffer->damageGranularity()->isPixelPerfect());
    }

    public function testPageSegmentBufferReportsFullWidthEightRowPages(): void
    {
        $granularity = (new PageSegmentBuffer(128, 64, $this->rowMajorSpec()))->damageGranularity();

        $this->assertSame(128, $granularity->unit_width);
        $this->assertSame(8, $granularity->unit_height);
    }

    public function testFullFramebufferReportsWholeSurfaceGranularity(): void
    {
        $buffer = new FullFramebuffer(128, 64, $this->rowMajorSpec());

        $this->assertTrue($buffer->damageGranularity()->coversWholeSurface());
    }

    /**
     * The partial-update strategies keep their grid across a present, because
     * refreshing only what changed presupposes a retained canvas. FullFramebuffer
     * resets, which is coherent for a strategy that always emits everything.
     */
    public function testOnlyThePartialUpdateBuffersAdvertiseRetainedContents(): void
    {
        $spec = $this->rowMajorSpec();

        $this->assertTrue((new PageSegmentBuffer(128, 64, $spec))->preservesContentsOnPresent());
        $this->assertTrue((new DirtyRegionsBuffer(128, 64, $spec))->preservesContentsOnPresent());
        $this->assertFalse((new FullFramebuffer(128, 64, $spec))->preservesContentsOnPresent());
    }

    protected function rowMajorSpec(): FormatSpec
    {
        return new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B16, endianness: Endianness::MSB);
    }
}
