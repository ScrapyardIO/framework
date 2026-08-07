<?php

namespace Fabricate\Core\Console;

use Fabricate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\confirm;

#[AsCommand(name: 'make:node')]
class NodeMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected string $name = 'make:node';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Create a new Flow Node class under app/Workflows';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Node';

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return $this->option('async')
            ? $this->resolveStubPath('/stubs/node.async.stub')
            : $this->resolveStubPath('/stubs/node.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
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
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Workflows';
    }

    /**
     * Interact further with the user if they were prompted for missing arguments.
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        if ($this->didReceiveOptions($input)) {
            return;
        }

        $input->setOption('async', confirm('Should the node use AsyncNode (concurrency-backed exec)?', default: false));
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the node already exists'],
            ['async', null, InputOption::VALUE_NONE, 'Create an AsyncNode (concurrency-backed exec)'],
        ];
    }
}
