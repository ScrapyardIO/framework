<?php

namespace Tests\Sketches\Fixtures;

use Fabricate\Contracts\Sketches\SketchLoopResult;
use Fabricate\Sketches\Sketch;

class CountingSketch extends Sketch
{
    /** @var list<string> */
    public array $calls = [];

    public int $loops = 0;

    public function __construct(public int $stopAfter = 1) {}

    public function boot(): void
    {
        $this->calls[] = 'boot';
    }

    public function loop(): SketchLoopResult
    {
        $this->loops++;
        $this->calls[] = 'loop';

        return $this->loops >= $this->stopAfter
            ? SketchLoopResult::STOP
            : SketchLoopResult::CONTINUE;
    }

    public function shutdown(): void
    {
        $this->calls[] = 'shutdown';
    }
}
