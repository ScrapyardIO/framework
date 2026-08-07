<?php

namespace Fabricate\Sketches\Flow;

use Fabricate\Contracts\Sketches\Sketch;
use Fabricate\Contracts\Sketches\SketchLoopResult;
use Fabricate\Sketches\SketchRunner;

class TickSketchNode extends Node
{
    public function prep(mixed &$shared): mixed
    {
        return $shared;
    }

    public function exec(mixed $prepRes): mixed
    {
        /** @var array<string, mixed> $shared */
        $shared = $prepRes;
        $runner = $shared['runner'] ?? null;
        $sketch = $shared['sketch'] ?? null;

        if ($runner instanceof SketchRunner && $runner->shouldStop()) {
            return SketchLoopResult::STOP->value;
        }

        if (! $sketch instanceof Sketch) {
            return SketchLoopResult::STOP->value;
        }

        return $sketch->loop()->value;
    }

    public function post(mixed &$shared, mixed $prepRes, mixed $execRes): mixed
    {
        return $execRes;
    }
}
