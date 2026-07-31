<?php

namespace Fabricate\Core\Console;

use Fabricate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:node')]
class NodeMakeCommand extends GeneratorCommand
{
    protected string $name = 'make:node';

    protected string $description = 'Create a new UX node class';

    protected $type = 'Node';

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/node.stub');
    }

    protected function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = $this->scrapyard_io->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Nodes';
    }

    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the node already exists'],
        ];
    }
}
