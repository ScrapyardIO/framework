<?php

namespace DeptOfScrapyardRobotics\Tests\UX;

use Fabricate\Contracts\UX\Enums\Axis;
use Fabricate\Contracts\UX\Enums\CrossAxisAlignment;
use Fabricate\Contracts\UX\Enums\Damage;
use Fabricate\Contracts\UX\Enums\MainAxisAlignment;
use Fabricate\Contracts\UX\Enums\Overflow;
use PHPUnit\Framework\TestCase;

class UXEnumsTest extends TestCase
{
    /**
     * Project convention: backed by string, cases fully uppercase, and the
     * backing value matching the case name so persisted values stay readable.
     */
    public function testEveryCaseIsUppercaseAndSelfBacked(): void
    {
        $enums = [Damage::class, Axis::class, MainAxisAlignment::class, CrossAxisAlignment::class, Overflow::class];

        foreach ($enums as $enum) {
            foreach ($enum::cases() as $case) {
                $this->assertSame(strtoupper($case->name), $case->name, "{$enum}::{$case->name}");
                $this->assertSame($case->name, $case->value, "{$enum}::{$case->name} backing value");
            }
        }
    }

    /**
     * Layout implies paint, so merging must never lose the wider requirement —
     * this is what stops a LAYOUT change being downgraded when a PAINT change to
     * the same node arrives in the same frame.
     */
    public function testLayoutDamageAlwaysWinsAMerge(): void
    {
        $this->assertSame(Damage::LAYOUT, Damage::PAINT->merge(Damage::LAYOUT));
        $this->assertSame(Damage::LAYOUT, Damage::LAYOUT->merge(Damage::PAINT));
        $this->assertSame(Damage::LAYOUT, Damage::LAYOUT->merge(Damage::LAYOUT));
        $this->assertSame(Damage::PAINT, Damage::PAINT->merge(Damage::PAINT));
    }

    public function testOnlyLayoutDamageRequiresLayout(): void
    {
        $this->assertTrue(Damage::LAYOUT->requiresLayout());
        $this->assertFalse(Damage::PAINT->requiresLayout());
    }

    public function testAxesAreEachOthersCross(): void
    {
        $this->assertSame(Axis::VERTICAL, Axis::HORIZONTAL->cross());
        $this->assertSame(Axis::HORIZONTAL, Axis::VERTICAL->cross());

        foreach (Axis::cases() as $axis) {
            $this->assertSame($axis, $axis->cross()->cross(), 'Crossing twice returns the original axis.');
        }
    }

    public function testAxisExtentSelectsTheRightDimension(): void
    {
        $this->assertSame(100, Axis::HORIZONTAL->extentOf(100, 50));
        $this->assertSame(50, Axis::VERTICAL->extentOf(100, 50));
    }

    public function testOnlyTheSpacingAlignmentsDistributeBetweenChildren(): void
    {
        $distributing = array_filter(
            MainAxisAlignment::cases(),
            static fn (MainAxisAlignment $case): bool => $case->distributesBetweenChildren(),
        );

        $this->assertSame(
            ['SPACE_BETWEEN', 'SPACE_AROUND', 'SPACE_EVENLY'],
            array_values(array_map(static fn (MainAxisAlignment $case): string => $case->name, $distributing)),
        );
    }

    public function testOnlyStretchResizesAChild(): void
    {
        $this->assertTrue(CrossAxisAlignment::STRETCH->resizesChild());

        foreach ([CrossAxisAlignment::START, CrossAxisAlignment::CENTER, CrossAxisAlignment::END] as $case) {
            $this->assertFalse($case->resizesChild(), $case->name);
        }
    }

    public function testOverflowClipsByDefaultCaseOnly(): void
    {
        $this->assertTrue(Overflow::CLIP->clips());
        $this->assertFalse(Overflow::VISIBLE->clips());
    }
}
