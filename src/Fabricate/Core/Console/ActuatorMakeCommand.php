<?php

namespace Fabricate\Core\Console;

use Fabricate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:actuator')]
class ActuatorMakeCommand extends GeneratorCommand
{
    protected string $name = 'make:actuator';

    protected string $description = 'Create a new actuator class';

    protected $type = 'Actuator';

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/actuator.stub');
    }

    protected function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = $this->scrapyard_io->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Actuators';
    }

    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the actuator already exists'],
        ];
    }
}
