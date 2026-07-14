<?php

namespace BareMetal\Core\Console;

use Exception;
use WeakMap;
use Throwable;
use SplFileInfo;
use DateTimeZone;
use ReflectionClass;
use DateTimeInterface;
use ReflectionException;
use Carbon\CarbonInterval;
use ScrapyardIO\NutsAndBolts\Str;
use ScrapyardIO\NutsAndBolts\Arr;
use ScrapyardIO\NutsAndBolts\Env;
use ScrapyardIO\NutsAndBolts\Carbon;
use Symfony\Component\Finder\Finder;
use BareMetal\Contracts\Core\Machine;
use ScrpayardIO\NutsAndBolts\Collection;
use BareMetal\Console\Machine as Workshop;
use BareMetal\Contracts\Events\Dispatcher;
use Symfony\Component\Console\ConsoleEvents;
use BareMetal\Console\Events\CommandFinished;
use BareMetal\Console\Events\CommandStarting;
use Symfony\Component\Console\Command\Command;
use BareMetal\Contracts\Debug\ExceptionHandler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use ScrapyardIO\NutsAndBolts\Concerns\InteractsWithTime;
use BareMetal\Contracts\Console\Kernel as KernelContract;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use BareMetal\Contracts\Chassis\BindingResolutionException;
use BareMetal\Contracts\Chassis\CircularDependencyException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Exception\CommandNotFoundException;

class ConsoleKernel implements KernelContract
{
    use InteractsWithTime;

    /**
     * The application implementation.
     *
     */
    protected Machine $app;

    /**
     * The event dispatcher implementation.
     */
    protected Dispatcher $events;

    /**
     * The Symfony event dispatcher implementation.
     */
    protected ?EventDispatcherInterface $symfony_dispatcher = null;

    /**
     * The Workshop application instance.
     */
    protected ?Workshop $workshop = null;

    /**
     * The Workshop commands provided by the application.
     */
    protected array $commands = [];

    /**
     * The paths where Workshop commands should be automatically discovered.
     */
    protected array $command_paths = [];

    /**
     * The paths where Workshop "routes" should be automatically discovered.
     */
    protected array $command_route_paths = [];

    /**
     * Indicates if the Closure commands have been loaded.
     */
    protected bool $commands_loaded = false;

    /**
     * The commands paths that have been "loaded".
     */
    protected array $loaded_paths = [];

    /**
     * Every registered command-duration handler.
     */
    protected array $command_lifecycle_duration_handlers = [];

    /**
     * When the currently handled command started.
     */
    protected ?Carbon $command_started_at;

    /**
     * The bootstrap classes for the application.
     */
    protected array $bootstrappers = [
        \BareMetal\Core\Bootstrap\LoadEnvironmentVariables::class,
        \BareMetal\Core\Bootstrap\LoadConfiguration::class,
        \BareMetal\Core\Bootstrap\HandleExceptions::class,
        \BareMetal\Core\Bootstrap\RegisterMagicAliases::class,
        \BareMetal\Core\Bootstrap\RegisterProviders::class,
        \BareMetal\Core\Bootstrap\BootProviders::class,
    ];

    /**
     * Create a new console kernel instance.
     */
    public function __construct(
        Machine $app,
        Dispatcher $events
    )
    {
        if (! defined('WORKSHOP_BINARY')) {
            define('WORKSHOP_BINARY', 'workshop');
        }

        $this->app = $app;
        $this->events = $events;

        $this->app->booted(function () {
            if (! $this->app->runningUnitTests()) {
                $this->rerouteSymfonyCommandEvents();
            }
        });
    }

    /**
     * Re-route the Symfony command events to their Laravel counterparts.
     */
    public function rerouteSymfonyCommandEvents(): static
    {
        if (is_null($this->symfony_dispatcher)) {
            $this->symfony_dispatcher = new EventDispatcher;

            $this->symfony_dispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) {
                $this->events->dispatch(
                    new CommandStarting($event->getCommand()?->getName() ?? '', $event->getInput(), $event->getOutput())
                );
            });

            $this->symfony_dispatcher->addListener(ConsoleEvents::TERMINATE, function (ConsoleTerminateEvent $event) {
                $this->events->dispatch(
                    new CommandFinished($event->getCommand()?->getName() ?? '', $event->getInput(), $event->getOutput(), $event->getExitCode())
                );
            });
        }

        return $this;
    }

    /**
     * Run the console application.
     */
    public function handle(InputInterface $input, ?OutputInterface $output = null): int
    {
        $this->command_started_at = Carbon::now();

        try {
            if (in_array($input->getFirstArgument(), ['env:encrypt', 'env:decrypt'], true)) {
                $this->bootstrapWithoutBootingProviders();
            }

            $this->bootstrap();

            return $this->getWorkshop()->run($input, $output);
        } catch (Throwable $e) {
            $this->reportException($e);

            $this->renderException($output ?? new \Symfony\Component\Console\Output\ConsoleOutput, $e);

            return 1;
        }
    }

    /**
     * Terminate the application.
     */
    public function terminate(InputInterface $input, int $status): void
    {
        //$this->events->dispatch(new Terminating());

        $this->app->terminate();

        if ($this->command_started_at === null) {
            return;
        }

        $this->command_started_at->setTimezone($this->app['config']->get('scrapyard-io.timezone') ?? 'UTC');

        foreach ($this->command_lifecycle_duration_handlers as ['threshold' => $threshold, 'handler' => $handler]) {
            $end ??= Carbon::now();

            if ($this->command_started_at->diffInMilliseconds($end) > $threshold) {
                $handler($this->command_started_at, $input, $status);
            }
        }

        $this->command_started_at = null;
    }

    /**
     * Register a callback to be invoked when the command lifecycle duration exceeds a given amount of time.
     */
    public function whenCommandLifecycleIsLongerThan(DateTimeInterface|CarbonInterval|float|int $threshold, callable $handler): void
    {
        $threshold = $threshold instanceof DateTimeInterface
            ? $this->secondsUntil($threshold) * 1000
            : $threshold;

        $threshold = $threshold instanceof CarbonInterval
            ? $threshold->totalMilliseconds
            : $threshold;

        $this->command_lifecycle_duration_handlers[] = [
            'threshold' => $threshold,
            'handler' => $handler,
        ];
    }

    /**
     * When the command being handled started.
     */
    public function command_started_at(): ?Carbon
    {
        return $this->command_started_at;
    }

    /**
     * Define the application's command schedule.
     *
     * @param  \BareMetal\Console\Scheduling\Schedule  $schedule
     * @return void
     *//*
    protected function schedule(Schedule $schedule)
    {
        //
    }*/

    /**
     * Resolve a console schedule instance.
     *
     * @return \BareMetal\Console\Scheduling\Schedule
     */ /*
    public function resolveConsoleSchedule()
    {
        return tap(new Schedule($this->scheduleTimezone()), function ($schedule) {
            $this->schedule($schedule->useCache($this->scheduleCache()));
        });
    }*/

    /**
     * Get the timezone that should be used by default for scheduled events.
     */
    protected function scheduleTimezone(): DateTimeZone|string|null
    {
        $config = $this->app['config'];

        return $config->get('scrapyard-io.schedule_timezone', $config->get('scrapyard-io.timezone'));
    }

    /**
     * Get the name of the cache store that should manage scheduling mutexes.
     */
    protected function scheduleCache(): ?string
    {
        return $this->app['config']->get('cache.schedule_store', Env::get('SCHEDULE_CACHE_DRIVER', function () {
            return Env::get('SCHEDULE_CACHE_STORE');
        }));
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
     * Register a Closure based command with the application.
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

        $this->loaded_paths = array_values(
            array_unique(array_merge($this->loaded_paths, $paths))
        );

        $namespace = $this->app->getNamespace();

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
     */
    protected function findCommands(array $paths): Finder
    {
        return Finder::create()->in($paths)->name('*.php')->files();
    }

    /**
     * Extract the command class name from the given file path.
     *
     * @throws BindingResolutionException|CircularDependencyException|ReflectionException
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
     * @throws BindingResolutionException|CircularDependencyException|ReflectionException
     */
    public function registerCommand(Command $command): void
    {
        $this->getWorkshop()->add($command);
    }

    /**
     * Run a Workshop console command by name.
     *
     * @throws CommandNotFoundException
     * @throws Exception
     */
    public function call(string|Command $command, array $parameters = [], ?OutputInterface $outputBuffer = null): int
    {
        if (in_array($command, ['env:encrypt', 'env:decrypt'], true)) {
            $this->bootstrapWithoutBootingProviders();
        }

        $this->bootstrap();

        return $this->getWorkshop()->call($command, $parameters, $outputBuffer);
    }

    /**
     * Queue the given console command.
     * @return \BareMetal\Core\Bus\PendingDispatch
     *//*
    public function queue(string $command, array $parameters = [])
    {
        return QueuedCommand::dispatch(func_get_args());
    }*/

    /**
     * Get every command registered with the console.
     * @throws BindingResolutionException|CircularDependencyException|ReflectionException
     */
    public function all(): array
    {
        $this->bootstrap();

        return $this->getWorkshop()->all();
    }

    /**
     * Get the output for the last run command.
     * @throws BindingResolutionException|CircularDependencyException|ReflectionException
     */
    public function output(): string
    {
        $this->bootstrap();

        return $this->getWorkshop()->output();
    }

    /**
     * Bootstrap the application for workshop commands.
     */
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
     * Discover the commands that should be automatically loaded.
     */
    protected function discoverCommands(): void
    {
        foreach ($this->command_paths as $path) {
            $this->load($path);
        }

        foreach ($this->command_route_paths as $path) {
            if (file_exists($path)) {
                require $path;
            }
        }
    }

    /**
     * Bootstrap the application without booting service providers.
     */
    public function bootstrapWithoutBootingProviders(): void
    {
        $this->app->bootstrapWith(
            (new Collection($this->bootstrappers()))
                ->reject(fn ($bootstrapper) => $bootstrapper === \BareMetal\Core\Bootstrap\BootProviders::class)
                ->all()
        );
    }

    /**
     * Determine if the kernel should discover commands.
     */
    protected function shouldDiscoverCommands(): bool
    {
        return get_class($this) === __CLASS__;
    }

    /**
     * Get the Workshop application instance.
     * @throws BindingResolutionException|ReflectionException
     */
    protected function getWorkshop(): Workshop
    {
        if (is_null($this->workshop)) {
            $this->workshop = (new Workshop($this->app,
                $this->events,
                $this->app->version())
            )->resolveCommands($this->commands)
                ->setContainerCommandLoader();

            if ($this->symfony_dispatcher instanceof EventDispatcher) {
                $this->workshop->setDispatcher($this->symfony_dispatcher);
                $this->workshop->setSignalsToDispatchEvent();
            }
        }

        return $this->workshop;
    }

    /**
     * Set the Workshop application instance.
     */
    public function setWorkshop(?Machine $workshop): void
    {
        $this->workshop = $workshop;
    }

    /**
     * Set the Workshop commands provided by the application.
     */
    public function addCommands(array $commands): static
    {
        $this->commands = array_values(array_unique(array_merge($this->commands, $commands)));

        return $this;
    }

    /**
     * Set the paths that should have their Workshop commands automatically discovered.
     */
    public function addCommandPaths(array $paths): static
    {
        $this->command_paths = array_values(array_unique(array_merge($this->command_paths, $paths)));

        return $this;
    }

    /**
     * Set the paths that should have their Workshop "routes" automatically discovered.
     */
    public function addCommandRoutePaths(array $paths): static
    {
        $this->command_route_paths = array_values(array_unique(array_merge($this->command_route_paths, $paths)));

        return $this;
    }

    /**
     * Get the bootstrap classes for the application.
     */
    protected function bootstrappers(): array
    {
        return $this->bootstrappers;
    }

    /**
     * Report the exception to the exception handler.
     */
    protected function reportException(Throwable $e): void
    {
        $this->app[ExceptionHandler::class]->report($e);
    }

    /**
     * Render the given exception.
     */
    protected function renderException(OutputInterface $output, Throwable $e): void
    {
        $this->app[ExceptionHandler::class]->renderForConsole($output, $e);
    }
}
