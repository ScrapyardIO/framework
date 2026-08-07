<?php

namespace Fabricate\Sketches;

use Fabricate\Console\Concerns\InteractsWithIO;
use Fabricate\Console\OutputStyle;
use Fabricate\Contracts\Sketches\Sketch as SketchContract;
use Fabricate\Contracts\Sketches\SketchLoopResult;
use Symfony\Component\Console\Input\InputInterface;

abstract class Sketch implements SketchContract
{
    use InteractsWithIO;

    /**
     * The sketch description.
     *
     * @var string
     */
    protected string $description = '';

    /**
     * Middleware for this sketch (class-strings / callables), merged after global stack.
     *
     * @var array<int, class-string|callable|object>
     */
    protected array $middleware = [];

    /**
     * Bind console input/output for use during the sketch lifecycle.
     */
    public function configureIO(InputInterface $input, OutputStyle $output): void
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Get the sketch description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return array<int, class-string|callable|object>
     */
    public function middleware(): array
    {
        return $this->middleware;
    }

    /**
     * Prepare the sketch before the first loop tick.
     */
    public function boot(): void
    {
    }

    /**
     * Execute one cooperative tick of the sketch.
     */
    abstract public function loop(): SketchLoopResult;

    /**
     * Release resources after the loop ends or fails.
     */
    public function shutdown(): void
    {
    }
}
