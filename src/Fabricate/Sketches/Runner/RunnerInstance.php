<?php

namespace Fabricate\Sketches\Runner;

use Fabricate\Contracts\Chassis\ServiceContainer;
use Fabricate\Contracts\Sketches\SketchRegistry;
use Fabricate\Pipeline\Pipeline;
use Fabricate\Sketches\SketchRunner;
use ReflectionClass;
use Symfony\Component\Console\Application as SymfonyApplication;

class RunnerInstance extends SymfonyApplication
{
    /**
     * @param  array<int, class-string|callable|object>  $globalMiddleware
     */
    public function __construct(
        protected ServiceContainer $container,
        protected SketchRegistry $registry,
        protected SketchRunner $runner,
        string $version,
        protected array $globalMiddleware = [],
    ) {
        parent::__construct('ScrapyardIO Runner', $version);

        $this->setAutoExit(false);
        $this->setCatchExceptions(false);

        $this->registerSketches();
    }

    protected function registerSketches(): void
    {
        foreach ($this->registry->all() as $name => $class) {
            $description = $this->descriptionFor($class);

            $this->addCommand(new RunSketchCommand(
                sketchName: $name,
                sketchDescription: $description,
                registry: $this->registry,
                runner: $this->runner,
                pipeline: new Pipeline($this->container),
                globalMiddleware: $this->globalMiddleware,
            ));
        }
    }

    /**
     * @param  class-string  $class
     */
    protected function descriptionFor(string $class): string
    {
        try {
            $defaults = (new ReflectionClass($class))->getDefaultProperties();

            return is_string($defaults['description'] ?? null) ? $defaults['description'] : '';
        } catch (\ReflectionException) {
            return '';
        }
    }
}
