<?php

namespace Fabricate\Sketches\Runner;

use Fabricate\Console\OutputStyle;
use Fabricate\Contracts\Sketches\SketchRegistry;
use Fabricate\Pipeline\Pipeline;
use Fabricate\Sketches\Middleware\DispatchSketch;
use Fabricate\Sketches\Sketch;
use Fabricate\Sketches\SketchRunContext;
use Fabricate\Sketches\SketchRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Symfony command that runs one registered sketch through middleware + Flow.
 */
class RunSketchCommand extends Command
{
    /**
     * @param  array<int, class-string|callable|object>  $globalMiddleware
     */
    public function __construct(
        protected string $sketchName,
        protected string $sketchDescription,
        protected SketchRegistry $registry,
        protected SketchRunner $runner,
        protected Pipeline $pipeline,
        protected array $globalMiddleware = [],
    ) {
        parent::__construct($sketchName);

        $this->setDescription($sketchDescription !== '' ? $sketchDescription : "Run the [{$sketchName}] sketch");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sketch = $this->registry->resolve($this->sketchName);
        $style = new OutputStyle($input, $output);

        if ($sketch instanceof Sketch) {
            $sketch->configureIO($input, $style);
        }

        $middleware = array_values(array_merge(
            $this->globalMiddleware,
            $sketch instanceof Sketch ? $sketch->middleware() : [],
        ));

        $context = new SketchRunContext(
            name: $this->sketchName,
            sketch: $sketch,
            runner: $this->runner,
            input: $input,
            output: $style,
        );

        return (new DispatchSketch($this->pipeline, $this->runner, $middleware))->run($context);
    }
}
