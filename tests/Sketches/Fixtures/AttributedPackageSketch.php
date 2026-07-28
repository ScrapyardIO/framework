<?php

namespace DeptOfScrapyardRobotics\Tests\Sketches\Fixtures;

use Fabricate\Contracts\Sketches\Attributes\Sketch;
use Fabricate\Contracts\Sketches\SketchLoopResult;
use Fabricate\Sketches\Sketch as BaseSketch;

#[Sketch('package-blink')]
class AttributedPackageSketch extends BaseSketch
{
    /**
     * The sketch description.
     *
     * @var string
     */
    protected string $description = 'Attributed package blink sketch.';

    public function loop(): SketchLoopResult
    {
        return SketchLoopResult::STOP;
    }
}
