<?php

namespace Fabricate\Core\Console;

use Fabricate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:sensor')]
class SensorMakeCommand extends GeneratorCommand
{
    protected string $name = 'make:sensor';

    protected string $description = 'Create a new sensor class';

    protected $type = 'Sensor';

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/sensor.stub');
    }

    protected function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = $this->scrapyard_io->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Sensors';
    }

    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the sensor already exists'],
        ];
    }
}
