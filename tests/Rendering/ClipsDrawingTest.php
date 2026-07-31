<?php

namespace DeptOfScrapyardRobotics\Tests\Rendering;

use Fabricate\NutsAndBolts\Geometry\Rect;
use Fabricate\Rendering\Concerns\ClipsDrawing;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ClipsDrawingTest extends TestCase
{
    public function testAnEmptyStackMeansUnrestrictedDrawing(): void
    {
        $subject = new ClipStackSubject;

        $this->assertNull($subject->clip());
        $this->assertTrue($subject->allows(-500, -500));
        $this->assertSame([0, 0, 9, 9], $subject->segment(0, 0, 10, 10)?->toBounds());
    }

    public function testPushingRestrictsDrawingToTheRegion(): void
    {
        $subject = new ClipStackSubject;
        $subject->pushClip(new Rect(10, 10, 10, 10));

        $this->assertTrue($subject->allows(10, 10));
        $this->assertTrue($subject->allows(19, 19));
        $this->assertFalse($subject->allows(9, 10));
        $this->assertFalse($subject->allows(20, 20));
    }

    public function testNestedPushesIntersectSoAChildCannotWidenItsParent(): void
    {
        $subject = new ClipStackSubject;
        $subject->pushClip(new Rect(10, 10, 10, 10));
        $subject->pushClip(new Rect(0, 0, 100, 100));

        $this->assertSame([10, 10, 19, 19], $subject->clip()?->toBounds());
    }

    public function testNestedPushesNarrowToTheOverlap(): void
    {
        $subject = new ClipStackSubject;
        $subject->pushClip(new Rect(0, 0, 20, 20));
        $subject->pushClip(new Rect(15, 15, 20, 20));

        $this->assertSame([15, 15, 19, 19], $subject->clip()?->toBounds());
    }

    public function testDisjointNestedClipsRejectEverything(): void
    {
        $subject = new ClipStackSubject;
        $subject->pushClip(new Rect(0, 0, 10, 10));
        $subject->pushClip(new Rect(50, 50, 10, 10));

        $this->assertTrue($subject->clip()?->isEmpty());
        $this->assertFalse($subject->allows(5, 5));
        $this->assertFalse($subject->allows(55, 55));
        $this->assertNull($subject->segment(0, 0, 100, 100));
    }

    public function testPoppingRestoresTheParentRegion(): void
    {
        $subject = new ClipStackSubject;
        $subject->pushClip(new Rect(0, 0, 20, 20));
        $subject->pushClip(new Rect(5, 5, 5, 5));
        $subject->popClip();

        $this->assertSame([0, 0, 19, 19], $subject->clip()?->toBounds());

        $subject->popClip();

        $this->assertNull($subject->clip());
    }

    public function testPoppingAnEmptyStackIsHarmless(): void
    {
        $subject = new ClipStackSubject;
        $subject->popClip()->popClip();

        $this->assertNull($subject->clip());
    }

    public function testSegmentsAreClippedAnalyticallyRatherThanRejected(): void
    {
        $subject = new ClipStackSubject;
        $subject->pushClip(new Rect(8, 8, 8, 8));

        // Overdraws the clip on all four sides; survives as exactly the clip.
        $this->assertSame([8, 8, 15, 15], $subject->segment(0, 0, 32, 32)?->toBounds());

        // Overlaps only the bottom-right corner.
        $this->assertSame([12, 12, 15, 15], $subject->segment(12, 12, 20, 20)?->toBounds());
    }

    public function testFullyClippedSegmentsAreRejected(): void
    {
        $subject = new ClipStackSubject;
        $subject->pushClip(new Rect(8, 8, 8, 8));

        $this->assertNull($subject->segment(0, 0, 4, 4));
        $this->assertNull($subject->segment(16, 16, 4, 4));
    }

    public function testNonPositiveSegmentsAreRejectedWithOrWithoutAClip(): void
    {
        $subject = new ClipStackSubject;

        $this->assertNull($subject->segment(0, 0, 0, 10));
        $this->assertNull($subject->segment(0, 0, 10, -2));

        $subject->pushClip(new Rect(0, 0, 20, 20));

        $this->assertNull($subject->segment(0, 0, 10, 0));
    }

    public function testWithClipPopsEvenWhenTheCallbackThrows(): void
    {
        $subject = new ClipStackSubject;
        $subject->pushClip(new Rect(0, 0, 20, 20));

        try {
            $subject->withClip(new Rect(5, 5, 5, 5), function (): void {
                throw new RuntimeException('paint failed');
            });
        } catch (RuntimeException) {
            // The point is the stack state below, not the exception itself.
        }

        $this->assertSame([0, 0, 19, 19], $subject->clip()?->toBounds());
    }

    public function testWithClipReturnsTheCallbackResult(): void
    {
        $subject = new ClipStackSubject;

        $bounds = $subject->withClip(
            new Rect(4, 4, 4, 4),
            fn (): ?array => $subject->clip()?->toBounds(),
        );

        $this->assertSame([4, 4, 7, 7], $bounds);
        $this->assertNull($subject->clip());
    }

    public function testClearClipsDropsEveryNestedRegion(): void
    {
        $subject = new ClipStackSubject;
        $subject->pushClip(new Rect(0, 0, 20, 20))
            ->pushClip(new Rect(2, 2, 5, 5))
            ->clearClips();

        $this->assertNull($subject->clip());
    }
}

/**
 * Exposes the trait's protected enforcement helpers so they can be asserted
 * directly, without dragging a renderer and framebuffer into these cases.
 */
class ClipStackSubject
{
    use ClipsDrawing;

    public function allows(int $x, int $y): bool
    {
        return $this->clipAllows($x, $y);
    }

    public function segment(int $x, int $y, int $width, int $height): ?Rect
    {
        return $this->clipSegment($x, $y, $width, $height);
    }
}
