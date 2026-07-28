<?php

namespace Fabricate\Contracts\Sketches;

interface Sketch
{
    /**
     * Prepare the sketch before the first loop tick.
     */
    public function boot(): void;

    /**
     * Execute one cooperative tick of the sketch.
     */
    public function loop(): SketchLoopResult;

    /**
     * Release resources after the loop ends or fails.
     */
    public function shutdown(): void;
}
