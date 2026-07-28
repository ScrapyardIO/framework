<?php

namespace DeptOfScrapyardRobotics\Tests\Sketches\Fixtures;

use Fabricate\Contracts\Sketches\Attributes\Sketch;
use Fabricate\Contracts\Sketches\SketchLoopResult;
use Fabricate\Sketches\Sketch as BaseSketch;

#[Sketch('injected-sketch')]
class InjectedSketch extends BaseSketch
{
    public ?Dependency $lifecycleDependency = null;

    public function __construct(public Dependency $dependency) {}

    public function boot(): void
    {
        //
    }

    public function loop(Dependency $dependency = new Dependency('lifecycle')): SketchLoopResult
    {
        $this->lifecycleDependency = $dependency;

        return SketchLoopResult::STOP;
    }

    public function shutdown(): void
    {
        //
    }
}
