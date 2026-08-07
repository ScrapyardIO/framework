<?php

namespace Fabricate\Sketches\Middleware;

use Closure;
use Fabricate\Pipeline\Pipeline;
use Fabricate\Sketches\SketchRunContext;
use Fabricate\Sketches\SketchRunner;

/**
 * Dispatch a sketch run through a Pipeline middleware onion, destination = Flow runner.
 */
class DispatchSketch
{
    /**
     * @param  array<int, class-string|object|callable>  $middleware
     */
    public function __construct(
        protected Pipeline $pipeline,
        protected SketchRunner $runner,
        protected array $middleware = [],
    ) {}

    public function run(SketchRunContext $context): int
    {
        return (int) $this->pipeline
            ->send($context)
            ->through($this->middleware)
            ->then(function (SketchRunContext $context) {
                $context->exitStatus = $this->runner->run($context->sketch);

                return $context->exitStatus;
            });
    }
}
