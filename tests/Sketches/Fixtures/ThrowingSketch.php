<?php

namespace DeptOfScrapyardRobotics\Tests\Sketches\Fixtures;

use Fabricate\Contracts\Sketches\SketchLoopResult;
use Fabricate\Sketches\Sketch;
use RuntimeException;

class ThrowingSketch extends Sketch
{
    /** @var list<string> */
    public array $calls = [];

    public function __construct(protected string $phase = 'loop') {}

    public function boot(): void
    {
        $this->calls[] = 'boot';

        if ($this->phase === 'boot') {
            throw new RuntimeException('boot failed');
        }
    }

    public function loop(): SketchLoopResult
    {
        $this->calls[] = 'loop';

        throw new RuntimeException('loop failed');
    }

    public function shutdown(): void
    {
        $this->calls[] = 'shutdown';
    }
}
