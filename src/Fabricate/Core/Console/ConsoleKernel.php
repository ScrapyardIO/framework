<?php

namespace Fabricate\Core\Console;

use Carbon\CarbonInterval;
use DateTimeInterface;
use Fabricate\Console\Command;
use Fabricate\Console\ConsoleMachine;
use Fabricate\Console\ConsoleMachine as Workshop;
use Fabricate\Console\Events\CommandFinished;
use Fabricate\Console\Events\CommandStarting;
use Fabricate\Contracts\Chassis\BindingResolutionException;
use Fabricate\Contracts\Console\ConsoleKernel as KernelContract;
use Fabricate\Contracts\Core\Machine;
use Fabricate\Contracts\Debug\ExceptionHandler;
use Fabricate\Contracts\Events\Dispatcher;
use Fabricate\Core\Events\Terminating;
use Fabricate\NutsAndBolts\Arr;
use Fabricate\NutsAndBolts\Carbon;
use Fabricate\NutsAndBolts\Collection;
use Fabricate\NutsAndBolts\Concerns\InteractsWithTime;
use Fabricate\NutsAndBolts\Str;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;
use WeakMap;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class ConsoleKernel implements KernelContract
{
    use InteractsWithTime;

    /**
     * The application implementation.
     *
     * @var Machine
     */
    protected Machine $machine;

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
     * @var ConsoleMachine|null
     */
    protected ?ConsoleMachine $workshop = null;

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
     * @param Machine $machine
     * @param ?Dispatcher $events
     */
    public function __construct(Machine $machine, ?Dispatcher $events = null)
    {
        if (! defined('WORKSHOP_BINARY')) {
            define('WORKSHOP_BINARY', 'workshop');
        }

        $this->machine = $machine;
        if($events)
        {
            $this->events = $events;
        }

        $this->machine->booted(function () {
            if (! $this->machine->runningUnitTests()) {
                $this->rerouteSymfonyCommandEvents();
            }
        });
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
     * Terminate the application.
     *
     * @param InputInterface $input
     * @param int $status
     * @return void
     */
    public function terminate(InputInterface $input, int $status): void
    {
        $this->events?->dispatch(new Terminating());

        $this->machine->terminate();

        if ($this->commandStartedAt === null) {
            return;
        }

        if ($this->machine->bound('config')) {
            $this->commandStartedAt->setTimezone($this->machine['config']->get('machine.timezone') ?? 'UTC');
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
     * Register a callback to be invoked when the command lifecycle duration exceeds a given amount of time.
     *
     * @param float|DateTimeInterface|int|CarbonInterval $threshold
     * @param callable $handler
     * @return void
     */
    public function whenCommandLifecycleIsLongerThan(float|CarbonInterval|DateTimeInterface|int $threshold, callable $handler): void
    {
        $threshold = $threshold instanceof DateTimeInterface
            ? $this->secondsUntil($threshold) * 1000
            : $threshold;

        $threshold = $threshold instanceof CarbonInterval
            ? $threshold->totalMilliseconds
            : $threshold;

        $this->commandLifecycleDurationHandlers[] = [
            'threshold' => $threshold,
            'handler' => $handler,
        ];
    }

    /**
     * When the command being handled started.
     *
     * @return Carbon|null
     */
    public function commandStartedAt(): ?Carbon
    {
        return $this->commandStartedAt;
    }

    /**
     * Define the application's command schedule.
     *
     * @param  \Fabricate\Console\Scheduling\Schedule  $schedule
     * @return void
     *
    protected function schedule(Schedule $schedule)
    {
        //
    }

    /**
     * Resolve a console schedule instance.
     *
     * @return \Fabricate\Console\Scheduling\Schedule
     *
    public function resolveConsoleSchedule()
    {
        return tap(new Schedule($this->scheduleTimezone()), function ($schedule) {
            $this->schedule($schedule->useCache($this->scheduleCache()));
        });
    }

    /**
     * Get the timezone that should be used by default for scheduled events.
     *
     * @return \DateTimeZone|string|null
     *
    protected function scheduleTimezone()
    {
        $config = $this->machine['config'];

        return $config->get('machine.schedule_timezone', $config->get('machine.timezone'));
    }

    /**
     * Get the name of the cache store that should manage scheduling mutexes.
     *
     * @return string|null
     *
    protected function scheduleCache()
    {
        return $this->machine['config']->get('cache.schedule_store', Env::get('SCHEDULE_CACHE_DRIVER', function () {
            return Env::get('SCHEDULE_CACHE_STORE');
        }));
    }
    */

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
     * Register a Closure based command with the application.
     *
     * @param string $signature
     * @param  callable  $callback
     * @return ClosureCommand
     */
    public function command(string $signature, callable $callback): ClosureCommand
    {
        $command = new ClosureCommand($signature, $callback);

        Workshop::starting(function ($workshop) use ($command) {
            $workshop->add($command);
        });

        return $command;
    }

    /**
     * Register every command in the given directory.
     *
     * @param array|string $paths
     * @return void
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

        $namespace = $this->machine->getNamespace();

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
            Workshop::starting(function ($workshop) use ($file, $possibleCommands) {
                $workshop->resolve($possibleCommands[$file]);
            });
        }
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
     * Register the given command with the console application.
     *
     * @param  SymfonyCommand  $command
     * @return void
     */
    public function registerCommand(SymfonyCommand $command): void
    {
        $this->getWorkshop()->add($command);
    }

    /**
     * Run an Workshop console command by name.
     *
     * @param  SymfonyCommand|string  $command
     * @param  array  $parameters
     * @param OutputInterface|null $outputBuffer
     * @return int
     *
     * @throws CommandNotFoundException
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
     * Queue the given console command.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \Fabricate\Core\Bus\PendingDispatch
     *
    public function queue($command, array $parameters = [])
    {
        return QueuedCommand::dispatch(func_get_args());
    }*/

    /**
     * Get every command registered with the console.
     *
     * @return array
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
     */
    public function output(): string
    {
        $this->bootstrap();

        return $this->getWorkshop()->output();
    }

    /**
     * Bootstrap the application for workshop commands.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        if (! $this->machine->hasBeenBootstrapped()) {
            $this->machine->bootstrapWith($this->bootstrappers());
        }

        $this->machine->loadDeferredProviders();

        if (! $this->commandsLoaded) {
            $this->commands();

            if ($this->shouldDiscoverCommands()) {
                $this->discoverCommands();
            }

            $this->commandsLoaded = true;
        }
    }

    /**
     * Discover the commands that should be automatically loaded.
     *
     * @return void
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
     * Bootstrap the application without booting service providers.
     *
     * @return void
     */
    public function bootstrapWithoutBootingProviders(): void
    {
        $this->machine->bootstrapWith(
            new Collection($this->bootstrappers())
                ->reject(fn ($bootstrapper) => $bootstrapper === \Fabricate\Core\Bootstrap\BootProviders::class)
                ->all()
        );
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
     * Get the Workshop application instance.
     *
     * @return Workshop
     * @throws BindingResolutionException|\ReflectionException
     */
    protected function getWorkshop(): Workshop
    {
        if (is_null($this->workshop)) {
            $this->workshop = (new Workshop($this->machine, $this->events, $this->machine->version()))
                ->resolveCommands($this->commands)
                ->setContainerCommandLoader();

            if ($this->symfonyDispatcher instanceof EventDispatcher) {
                $this->workshop->setDispatcher($this->symfonyDispatcher);
                $this->workshop->setSignalsToDispatchEvent();
            }
        }

        return $this->workshop;
    }

    /**
     * Set the Workshop application instance.
     *
     * @param  \Fabricate\Console\Application|null  $workshop
     * @return void
     */
    public function setWorkshop($workshop)
    {
        $this->workshop = $workshop;
    }

    /**
     * Set the Workshop commands provided by the application.
     *
     * @param  array  $commands
     * @return $this
     */
    public function addCommands(array $commands)
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
    public function addCommandPaths(array $paths)
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
    public function addCommandRoutePaths(array $paths)
    {
        $this->commandRoutePaths = array_values(array_unique(array_merge($this->commandRoutePaths, $paths)));

        return $this;
    }

    /**
     * Get the bootstrap classes for the application.
     *
     * @return array
     */
    protected function bootstrappers()
    {
        return $this->bootstrappers;
    }

    /**
     * Report the exception to the exception handler.
     *
     * @param  \Throwable  $e
     * @return void
     */
    protected function reportException(Throwable $e)
    {
        $this->machine[ExceptionHandler::class]->report($e);
    }

    /**
     * Render the given exception.
     *
     * @param  OutputInterface  $output
     * @param  \Throwable  $e
     * @return void
     */
    protected function renderException($output, Throwable $e)
    {
        $this->machine[ExceptionHandler::class]->renderForConsole($output, $e);
    }
}
