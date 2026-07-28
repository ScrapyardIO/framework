<?php

namespace Fabricate\Core\Console;

use Fabricate\Console\GeneratorCommand;
use Fabricate\NutsAndBolts\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

use function Fabricate\Filesystem\Helpers\join_paths;

#[AsCommand(name: 'make:config', aliases: ['config:make'])]
class ConfigMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected string $name = 'make:config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Create a new configuration file';

    /**
     * The type of file being generated.
     *
     * @var string
     */
    protected $type = 'Config';

    /**
     * The console command name aliases.
     *
     * @var array<int, string>
     */
    protected array $aliases = ['config:make'];

    /**
     * Get the destination file path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name): string
    {
        return config_path(Str::finish($this->argument('name'), '.php'));
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        $relativePath = join_paths('stubs', 'config.stub');

        return file_exists($customPath = $this->scrapyard_io->basePath($relativePath))
            ? $customPath
            : join_paths(__DIR__, $relativePath);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the configuration file even if it already exists'],
        ];
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'What should the configuration file be named?',
        ];
    }
}
