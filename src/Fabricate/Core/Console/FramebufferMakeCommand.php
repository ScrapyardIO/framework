<?php

namespace Fabricate\Core\Console;

use Fabricate\Console\GeneratorCommand;
use Fabricate\NutsAndBolts\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:framebuffer')]
class FramebufferMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected string $name = 'make:framebuffer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Create a new framebuffer strategy class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Framebuffer';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/framebuffer.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = $this->scrapyard_io->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Framebuffers';
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        $class = str_replace($this->getNamespace($name).'\\', '', $name);
        $registration = Str::kebab($class);

        return str_replace(
            ['DummyName', '{{ name }}', '{{name}}'],
            $registration,
            $stub,
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the framebuffer already exists'],
        ];
    }
}
