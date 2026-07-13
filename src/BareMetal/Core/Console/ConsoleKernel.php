<?php

namespace BareMetal\Core\Console;

use Throwable;
use ReflectionClass;
use SplFileInfo;
use BadMethodCallException;
use BareMetal\Contracts\Core\Application as ScrapyardAppInterface;
use Illuminate\Contracts\Console\Kernel as KernelContract;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class ConsoleKernel implements KernelContract
{
    /**
     * The application implementation.
     */
    protected ScrapyardAppInterface $app;

    /**
     * The ScrapyardIO console application instance.
     */
    protected ?SymfonyApplication $scrapyard_io = null;

    /**
     * The output from the previous command.
     */
    protected ?OutputInterface $last_output = null;

    /**
     * The ScrapyardIO commands provided by the application.
     */
    protected array $commands = [];

    /**
     * The paths where ScrapyardIO commands should be automatically discovered.
     */
    protected array $command_paths = [];

    /**
     * The command paths that have already been loaded.
     */
    protected array $loaded_paths = [];

    /**
     * Indicates if the Closure commands have been loaded.
     */
    protected bool $commands_loaded = false;

    /**
     * The bootstrap classes for the application.
     *
     * @var class-string[]
     */
    protected array $bootstrappers = [

    ];

    public function __construct(ScrapyardAppInterface $app)
    {
        if (! defined('SCRAPYARD_IO_BINARY')) {
            define('SCRAPYARD_IO_BINARY', 'scrapyard-io');
        }

        $this->app = $app;
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void {}

    public function bootstrap(): void
    {
        if (! $this->app->hasBeenBootstrapped()) {
            $this->app->bootstrapWith($this->bootstrappers());
        }

        $this->app->loadDeferredProviders();

        if (! $this->commands_loaded) {
            $this->commands();

            if ($this->shouldDiscoverCommands()) {
                $this->discoverCommands();
            }

            $this->commands_loaded = true;
        }
    }

    /**
     * Run the console application.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface|null  $output
     */
    public function handle($input, $output = null): int
    {
        try {
            $this->bootstrap();

            return $this->getScrapyardIo()->run($input, $output);
        } catch (Throwable $e) {
            $this->reportException($e);
            $this->renderException($output, $e);

            return 1;
        }
    }

    /**
     * Run a ScrapyardIO console command by name.
     *
     * @param  \Symfony\Component\Console\Command\Command|string  $command
     * @param  array  $parameters
     * @param  OutputInterface|null  $output_buffer
     */
    public function call($command, array $parameters = [], $output_buffer = null): int
    {
        $this->bootstrap();

        [$command_name, $input] = $this->parseCommand($command, $parameters);

        $this->last_output = $output_buffer ?: new BufferedOutput;

        return $this->getScrapyardIo()->run($input, $this->last_output);
    }

    /**
     * Queue a ScrapyardIO console command by name.
     *
     * @param  string  $command
     * @param  array  $parameters
     */
    public function queue($command, array $parameters = []): never
    {
        throw new BadMethodCallException('Queueing console commands is not supported.');
    }

    /**
     * Get every command registered with the console.
     */
    public function all(): array
    {
        $this->bootstrap();

        return $this->getScrapyardIo()->all();
    }

    /**
     * Get the output for the last run command.
     */
    public function output(): string
    {
        $this->bootstrap();

        return $this->last_output && method_exists($this->last_output, 'fetch')
            ? $this->last_output->fetch()
            : '';
    }

    /**
     * Terminate the application.
     *
     * @param  InputInterface  $input
     * @param  int  $status
     */
    public function terminate($input, $status): void
    {
        $this->app->terminate();
    }

    /**
     * Get the ScrapyardIO console application instance.
     */
    protected function getScrapyardIo(): SymfonyApplication
    {
        if (is_null($this->scrapyard_io)) {
            $this->scrapyard_io = new SymfonyApplication('ScrapyardIO', $this->app->version());
            $this->scrapyard_io->setAutoExit(false);
            $this->scrapyard_io->setCatchExceptions(false);

            foreach ($this->commands as $command) {
                if (is_string($command)) {
                    $command = $this->app->make($command);
                }

                $this->scrapyard_io->add($command);
            }
        }

        return $this->scrapyard_io;
    }

    /**
     * Parse the incoming command and its input.
     *
     * @param  \Symfony\Component\Console\Command\Command|string  $command
     * @param  array  $parameters
     * @return array{0: string, 1: InputInterface}
     */
    protected function parseCommand(mixed $command, array $parameters): array
    {
        if (is_object($command)) {
            $command = $command->getName();
        }

        if ($parameters === []) {
            $input = new StringInput((string) $command);
            $command_name = $this->getScrapyardIo()->getDefinition()->hasArgument('command')
                ? (new StringInput((string) $command))->getFirstArgument() ?? (string) $command
                : (string) $command;

            return [$command_name, $input];
        }

        array_unshift($parameters, $command);

        return [(string) $command, new ArrayInput($parameters)];
    }

    /**
     * Report the exception to the exception handler when available.
     */
    protected function reportException(Throwable $e): void
    {
        error_log($e->getMessage()."\n".$e->getTraceAsString());
    }

    /**
     * Render the exception to the console output.
     */
    protected function renderException(?OutputInterface $output, Throwable $e): void
    {
        if (is_null($output)) {
            return;
        }

        $output->writeln('<error>'.$e->getMessage().'</error>');
    }

    /**
     * @return class-string[]
     */
    protected function bootstrappers(): array
    {
        return $this->bootstrappers;
    }

    /**
     * Determine if the kernel should discover commands.
     */
    protected function shouldDiscoverCommands(): bool
    {
        return get_class($this) === __CLASS__;
    }

    protected function discoverCommands(): void
    {
        foreach ($this->command_paths as $path) {
            $this->load($path);
        }

        /**
            @todo - I don't think this is needed
        foreach ($this->commandRoutePaths as $path) {
            if (file_exists($path)) {
                require $path;
            }
        }*/
    }

    /**
     * Register every command in the given directory.
     *
     * Discovers concrete Symfony Console Command subclasses. A Scrapyard-specific
     * command base class can tighten this later.
     */
    protected function load(array|string $paths): void
    {
        $paths = array_unique(is_array($paths) ? $paths : [$paths]);

        $paths = array_values(array_filter($paths, function (string $path): bool {
            return is_dir($path);
        }));

        if ($paths === []) {
            return;
        }

        $this->loaded_paths = array_values(array_unique(array_merge($this->loaded_paths, $paths)));

        $namespace = $this->app->getNamespace();

        foreach ($this->findCommands($paths) as $file) {
            $command_class = $this->commandClassFromFile($file, $namespace);

            try {
                $reflection = new ReflectionClass($command_class);
            } catch (Throwable) {
                continue;
            }

            if (! $reflection->isSubclassOf(Command::class) || $reflection->isAbstract()) {
                continue;
            }

            $this->commands[] = $command_class;

            if (! is_null($this->scrapyard_io)) {
                $this->scrapyard_io->add($this->app->make($command_class));
            }
        }
    }

    /**
     * Get the Finder instance for discovering command files.
     */
    protected function findCommands(array $paths): Finder
    {
        return Finder::create()->in($paths)->name('*.php')->files();
    }

    /**
     * Extract the command class name from the given file path.
     */
    protected function commandClassFromFile(SplFileInfo $file, string $namespace): string
    {
        $app_path = realpath($this->app->basePath('app'));
        $real_path = $file->getRealPath();

        $relative = ($app_path !== false && str_starts_with($real_path, $app_path.DIRECTORY_SEPARATOR))
            ? substr($real_path, strlen($app_path) + 1)
            : $file->getFilename();

        return $namespace.str_replace(
            ['/', '.php'],
            ['\\', ''],
            $relative
        );
    }
}
