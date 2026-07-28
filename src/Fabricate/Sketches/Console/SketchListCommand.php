<?php

namespace Fabricate\Sketches\Console;

use Fabricate\Console\Command;
use Fabricate\Contracts\Sketches\SketchRegistry;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'sketch:list')]
class SketchListCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var ?string
     */
    protected ?string $signature = 'sketch:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'List registered sketches';

    /**
     * Execute the console command.
     */
    public function handle(SketchRegistry $registry): int
    {
        $sketches = $registry->all();

        if ($sketches === []) {
            $this->info('No sketches have been registered.');

            return self::SUCCESS;
        }

        ksort($sketches);

        $rows = [];

        foreach ($sketches as $name => $class) {
            $rows[] = [$name, $this->descriptionFor($class)];
        }

        $this->table(['Name', 'Description'], $rows);

        return self::SUCCESS;
    }

    /**
     * Read the default $description property without constructing the sketch.
     *
     * @param  class-string  $class
     */
    protected function descriptionFor(string $class): string
    {
        $defaults = (new ReflectionClass($class))->getDefaultProperties();
        $description = $defaults['description'] ?? '';

        return is_string($description) ? $description : '';
    }
}
