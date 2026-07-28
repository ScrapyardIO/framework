<?php

namespace Fabricate\Sketches\Console;

use Fabricate\Console\Command;
use Fabricate\Contracts\Sketches\SketchRegistry;
use Fabricate\Sketches\Sketch;
use Fabricate\Sketches\SketchRunner;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'sketch')]
class SketchCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var ?string
     */
    protected ?string $signature = 'sketch {name : The registered sketch to run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Run a registered Sketch in the foreground';

    /**
     * Execute the console command.
     */
    public function handle(SketchRegistry $registry, SketchRunner $runner): int
    {
        $sketch = $registry->resolve((string) $this->argument('name'));

        if ($sketch instanceof Sketch) {
            $sketch->configureIO($this->input, $this->output);
        }

        return $runner->run($sketch);
    }
}
