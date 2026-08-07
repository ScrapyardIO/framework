<?php

namespace Tests\Sketches\Fixtures;

use Fabricate\Contracts\Sketches\SketchLoopResult;
use Fabricate\Sketches\Sketch;
use RuntimeException;

class ThrowingSketch extends Sketch
{
    /** @var list<string> */
    public array $calls = [];

    public function __construct(public string $failAt = 'loop') {}

    public function boot(): void
    {
        $this->calls[] = 'boot';

        if ($this->failAt === 'boot') {
            throw new RuntimeException('boot failed');
        }
    }

    public function loop(): SketchLoopResult
    {
        $this->calls[] = 'loop';

        if ($this->failAt === 'loop') {
            throw new RuntimeException('loop failed');
        }

        return SketchLoopResult::STOP;
    }

    public function shutdown(): void
    {
        $this->calls[] = 'shutdown';
    }
}
