<?php

namespace DeptOfScrapyardRobotics\Tests\Sketches\Fixtures;

use Fabricate\Contracts\Sketches\SketchLoopResult;
use Fabricate\Sketches\Sketch;
use Fabricate\Sketches\SketchRunner;

class ExternalStopSketch extends Sketch
{
    /** @var list<string> */
    public array $calls = [];

    public function __construct(protected SketchRunner $runner) {}

    public function boot(): void
    {
        $this->calls[] = 'boot';
    }

    public function loop(): SketchLoopResult
    {
        $this->calls[] = 'loop';
        $this->runner->stop();

        return SketchLoopResult::CONTINUE;
    }

    public function shutdown(): void
    {
        $this->calls[] = 'shutdown';
    }
}
