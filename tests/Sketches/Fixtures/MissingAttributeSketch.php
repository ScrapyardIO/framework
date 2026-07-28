<?php

namespace DeptOfScrapyardRobotics\Tests\Sketches\Fixtures;

use Fabricate\Contracts\Sketches\SketchLoopResult;
use Fabricate\Sketches\Sketch;

class MissingAttributeSketch extends Sketch
{
    public function loop(): SketchLoopResult
    {
        return SketchLoopResult::STOP;
    }
}
