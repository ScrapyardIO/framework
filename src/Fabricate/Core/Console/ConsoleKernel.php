<?php

namespace Fabricate\Core\Console;


use Exception;
use Fabricate\Console\Command;
use Fabricate\Console\ConsoleProgram;
use Fabricate\Contracts\Chassis\BindingResolutionException;
use Fabricate\Contracts\Debug\ExceptionHandler;
use Fabricate\Core\Bootstrap\BootProviders;
use Fabricate\Core\Events\Terminating;
use Fabricate\NutsAndBolts\Arr;
use Fabricate\NutsAndBolts\Carbon;
use Fabricate\Contracts\Core\Program;
use Fabricate\Contracts\Events\Dispatcher;
use Fabricate\NutsAndBolts\Collection;
use Fabricate\NutsAndBolts\Str;
use ReflectionClass;
use ReflectionException;
use SplFileInfo;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\ConsoleEvents;
use Fabricate\Console\Events\CommandFinished;
use Fabricate\Console\Events\CommandStarting;
use Fabricate\Console\ConsoleProgram as Workshop;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Fabricate\NutsAndBolts\Concerns\InteractsWithTime;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Fabricate\Contracts\Console\ConsoleKernel as KernelContract;
use Throwable;
use WeakMap;

class ConsoleKernel implements KernelContract
{
    use InteractsWithTime;
    /**
     * The application implementation.
     *
     * @var Program
     */
    protected Program $program;

    /**
     * The event dispatcher implementation.
     *
     * @var Dispatcher|null
     */
    protected ?Dispatcher $events = null;

    /**
     * The Symfony event dispatcher implementation.
     *
     * @var EventDispatcherInterface|null
     */
    protected ?EventDispatcherInterface $symfonyDispatcher = null;

    /**
     * The Workshop application instance.
     *
     * @var Workshop|null
     */
    protected ?Workshop $workshop = null;

    /**
     * The Workshop commands provided by the application.
     *
     * @var array
     */
    protected array $commands = [];

    /**
     * The paths where Workshop commands should be automatically discovered.
     *
     * @var array
     */
    protected array $commandPaths = [];

    /**
     * The paths where Workshop "routes" should be automatically discovered.
     *
     * @var array
     */
    protected array $commandRoutePaths = [];

    /**
     * Indicates if the Closure commands have been loaded.
     *
     * @var bool
     */
    protected bool $commandsLoaded = false;

    /**
     * The commands paths that have been "loaded".
     *
     * @var array
     */
    protected array $loadedPaths = [];

    /**
     * Every registered command duration handler.
     *
     * @var array
     */
    protected array $commandLifecycleDurationHandlers = [];

    /**
     * When the currently handled command started.
     *
     * @var Carbon|null
     */
    protected ?Carbon $commandStartedAt;

    /**
     * The bootstrap classes for the application.
     *
     * @var string[]
     */
    protected array $bootstrappers = [
        \Fabricate\Core\Bootstrap\LoadEnvironmentVariables::class,
        \Fabricate\Core\Bootstrap\LoadConfiguration::class,
        \Fabricate\Core\Bootstrap\HandleExceptions::class,
        \Fabricate\Core\Bootstrap\RegisterMagicAliases::class,
        \Fabricate\Core\Bootstrap\RegisterProviders::class,
        \Fabricate\Core\Bootstrap\BootProviders::class,
    ];

    /**
     * Create a new console kernel instance.
     *
     * @param Program $program
     * @param ?Dispatcher $events
     */
    public function __construct(Program $program, ?Dispatcher $events = null)
    {
        if (! defined('WORKSHOP_BINARY')) {
            define('WORKSHOP_BINARY', 'workshop');
        }

        $this->program = $program;
        if($events)
        {
            $this->events = $events;
        }

        $this->program->booted(function () {
            if (! $this->program->runningUnitTests()) {
                $this->rerouteSymfonyCommandEvents();
            }
        });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        //
    }

    /**
     * Set the Workshop commands provided by the application.
     *
     * @param  array  $commands
     * @return $this
     */
    public function addCommands(array $commands): static
    {
        $this->commands = array_values(array_unique(array_merge($this->commands, $commands)));

        return $this;
    }

    /**
     * Set the paths that should have their Workshop commands automatically discovered.
     *
     * @param  array  $paths
     * @return $this
     */
    public function addCommandPaths(array $paths): static
    {
        $this->commandPaths = array_values(array_unique(array_merge($this->commandPaths, $paths)));

        return $this;
    }

    /**
     * Set the paths that should have their Workshop "routes" automatically discovered.
     *
     * @param  array  $paths
     * @return $this
     */
    public function addCommandRoutePaths(array $paths): static
    {
        $this->commandRoutePaths = array_values(array_unique(array_merge($this->commandRoutePaths, $paths)));

        return $this;
    }


    /**
     * Bootstrap the application for workshop commands.
     *
     * @return void
     * @throws ReflectionException
     */
    public function bootstrap(): void
    {
        if (! $this->program->hasBeenBootstrapped()) {
            $this->program->bootstrapWith($this->bootstrappers());
        }

        $this->program->loadDeferredProviders();

        if (! $this->commandsLoaded) {
            $this->commands();

            if ($this->shouldDiscoverCommands()) {
                $this->discoverCommands();
            }

            $this->commandsLoaded = true;
        }
    }

    /**
     * Determine if the kernel should discover commands.
     *
     * @return bool
     */
    protected function shouldDiscoverCommands(): bool
    {
        return get_class($this) === __CLASS__;
    }

    /**
     * Extract the command class name from the given file path.
     *
     * @param  \SplFileInfo  $file
     * @param  string  $namespace
     * @return string
     */
    protected function commandClassFromFile(SplFileInfo $file, string $namespace): string
    {
        return $namespace.str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($file->getRealPath(), realpath(app_path()).DIRECTORY_SEPARATOR)
            );
    }

    /**
     * Get the Finder instance for discovering command files.
     *
     * @param  array  $paths
     * @return Finder
     */
    protected function findCommands(array $paths): Finder
    {
        return Finder::create()->in($paths)->name('*.php')->files();
    }

    /**
     * Register every command in the given directory.
     *
     * @param array|string $paths
     * @return void
     * @throws ReflectionException
     */
    protected function load(array|string $paths): void
    {
        $paths = array_unique(Arr::wrap($paths));

        $paths = array_filter($paths, function ($path) {
            return is_dir($path);
        });

        if (empty($paths)) {
            return;
        }

        $this->loadedPaths = array_values(
            array_unique(array_merge($this->loadedPaths, $paths))
        );

        $namespace = $this->program->getNamespace();

        $possibleCommands = new WeakMap;

        $filterCommands = function (SplFileInfo $file) use ($namespace, &$possibleCommands) {
            $commandClassName = $this->commandClassFromFile($file, $namespace);

            $possibleCommands[$file] = $commandClassName;

            $command = rescue(fn () => new ReflectionClass($commandClassName), null, false);

            return $command instanceof ReflectionClass
                && $command->isSubClassOf(Command::class)
                && ! $command->isAbstract();
        };

        foreach ($this->findCommands($paths)->filter($filterCommands) as $file) {
            ConsoleProgram::starting(function ($workshop) use ($file, $possibleCommands) {
                $workshop->resolve($possibleCommands[$file]);
            });
        }
    }

    /**
     * Discover the commands that should be automatically loaded.
     *
     * @return void
     * @throws ReflectionException
     */
    protected function discoverCommands(): void
    {
        foreach ($this->commandPaths as $path) {
            $this->load($path);
        }

        foreach ($this->commandRoutePaths as $path) {
            if (file_exists($path)) {
                require $path;
            }
        }
    }

    /**
     * Run the console application.
     *
     * @param InputInterface $input
     * @param OutputInterface|null $output
     * @return int
     */
    public function handle(InputInterface $input, ?OutputInterface $output = null): int
    {
        $this->commandStartedAt = Carbon::now();

        try {
            if (in_array($input->getFirstArgument(), ['env:encrypt', 'env:decrypt'], true)) {
                $this->bootstrapWithoutBootingProviders();
            }

            $this->bootstrap();

            return $this->getWorkshop()->run($input, $output);
        } catch (Throwable $e) {
            $this->reportException($e);

            $this->renderException($output, $e);

            return 1;
        }
    }

    /**
     * Report the exception to the exception handler.
     *
     * @param  Throwable  $e
     * @return void
     */
    protected function reportException(Throwable $e): void
    {
        $this->program[ExceptionHandler::class]->report($e);
    }

    /**
     * Render the given exception.
     *
     * @param OutputInterface $output
     * @param  Throwable  $e
     * @return void
     */
    protected function renderException(OutputInterface $output, Throwable $e): void
    {
        $this->program[ExceptionHandler::class]->renderForConsole($output, $e);
    }

    /**
     * Run a Workshop console command by name.
     *
     * @param SymfonyCommand|string $command
     * @param array $parameters
     * @param OutputInterface|null $outputBuffer
     * @return int
     *
     * @throws CommandNotFoundException|BindingResolutionException|ReflectionException
     * @throws Exception
     */
    public function call($command, array $parameters = [], ?OutputInterface $outputBuffer = null): int
    {
        if (in_array($command, ['env:encrypt', 'env:decrypt'], true)) {
            $this->bootstrapWithoutBootingProviders();
        }

        $this->bootstrap();

        return $this->getWorkshop()->call($command, $parameters, $outputBuffer);
    }

    /**
     * Bootstrap the application without booting service providers.
     *
     * @return void
     */
    public function bootstrapWithoutBootingProviders(): void
    {
        $this->program->bootstrapWith(
            new Collection($this->bootstrappers())
                ->reject(fn ($bootstrapper) => $bootstrapper === BootProviders::class)
                ->all()
        );
    }

    /**
     * Get the bootstrap classes for the application.
     *
     * @return array
     */
    protected function bootstrappers(): array
    {
        return $this->bootstrappers;
    }

    /**
     * Get every command registered with the console.
     *
     * @return array
     * @throws BindingResolutionException|ReflectionException
     */
    public function all(): array
    {
        $this->bootstrap();

        return $this->getWorkshop()->all();
    }

    /**
     * Get the output for the last run command.
     *
     * @return string
     * @throws BindingResolutionException|ReflectionException
     */
    public function output(): string
    {
        $this->bootstrap();

        return $this->getWorkshop()->output();
    }

    /**
     * Terminate the application.
     *
     * @param InputInterface $input
     * @param int $status
     * @return void
     */
    public function terminate(InputInterface $input, int $status): void
    {
        $this->events?->dispatch(new Terminating());

        $this->program->terminate();

        if ($this->commandStartedAt === null) {
            return;
        }

        if ($this->program->bound('config')) {
            $this->commandStartedAt->setTimezone($this->program['config']->get('machine.timezone') ?? 'UTC');
        }

        foreach ($this->commandLifecycleDurationHandlers as ['threshold' => $threshold, 'handler' => $handler]) {
            $end ??= Carbon::now();

            if ($this->commandStartedAt->diffInMilliseconds($end) > $threshold) {
                $handler($this->commandStartedAt, $input, $status);
            }
        }

        $this->commandStartedAt = null;
    }

    /**
     * Re-route the Symfony command events to their ScrapyardIO counterparts.
     *
     * @internal
     *
     * @return $this
     */
    public function rerouteSymfonyCommandEvents(): static
    {
        if (is_null($this->symfonyDispatcher)) {
            $this->symfonyDispatcher = new EventDispatcher();

            $this->symfonyDispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) {
                $this->events->dispatch(
                    new CommandStarting($event->getCommand()?->getName() ?? '', $event->getInput(), $event->getOutput())
                );
            });

            $this->symfonyDispatcher->addListener(ConsoleEvents::TERMINATE, function (ConsoleTerminateEvent $event) {
                $this->events->dispatch(
                    new CommandFinished($event->getCommand()?->getName() ?? '', $event->getInput(), $event->getOutput(), $event->getExitCode())
                );
            });
        }

        return $this;
    }

    /**
     * Get the Workshop application instance.
     *
     * @return Workshop
     * @throws BindingResolutionException|ReflectionException
     */
    protected function getWorkshop(): Workshop
    {
        if (is_null($this->workshop)) {
            $this->workshop = new Workshop($this->program, $this->events, $this->program->version())
                ->resolveCommands($this->commands)
                ->setContainerCommandLoader();

            if ($this->symfonyDispatcher instanceof EventDispatcher) {
                $this->workshop->setDispatcher($this->symfonyDispatcher);
                $this->workshop->setSignalsToDispatchEvent();
            }
        }

        return $this->workshop;
    }
}