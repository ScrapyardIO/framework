<?php

namespace Fabricate\Core\Setup;

use Fabricate\Contracts\Console\ConsoleKernel;
use Fabricate\Core\Bootstrap\RegisterProviders;
use Fabricate\Core\Machine;
use Fabricate\NutsAndBolts\Collection;
use ReflectionException;
use Fabricate\Core\Support\Providers\EventServiceProvider as AppEventServiceProvider;

class AssemblyLine
{
    /**
     * The service provider that are marked for registration.
     *
     * @var array
     */
    protected array $pendingProviders = [];

    /**
     * Any additional routing callbacks that should be invoked while registering routes.
     *
     * @var array
     */
    protected array $additionalRoutingCallbacks = [];

    /**
     * The Folio / page middleware that have been defined by the user.
     *
     * @var array
     */
    protected array $pageMiddleware = [];

    /**
     * Create a new application builder instance.
     */
    public function __construct(protected Machine $machine) {}

    /**
     * Register the standard kernel classes for the application.
     *
     * @return $this
     * @throws ReflectionException
     */
    public function withKernels(): static
    {
        $this->machine->singleton(
            \Fabricate\Contracts\Console\ConsoleKernel::class,
            \Fabricate\Core\Console\ConsoleKernel::class,
        );

        return $this;
    }

    /**
     * Register the core event service provider for the application.
     *
     * @param  iterable<int, string>|bool  $discover
     * @return $this
     */
    public function withEvents(iterable|bool $discover = true): static
    {
        if (is_iterable($discover)) {
            AppEventServiceProvider::setEventDiscoveryPaths($discover);
        }

        if ($discover === false) {
            AppEventServiceProvider::disableEventDiscovery();
        }

        if (! isset($this->pendingProviders[AppEventServiceProvider::class])) {
            $this->machine->booting(function () {
                $this->machine->register(AppEventServiceProvider::class);
            });
        }

        $this->pendingProviders[AppEventServiceProvider::class] = true;

        return $this;
    }

    /**
     * Register additional Artisan commands with the application.
     *
     * @param  array  $commands
     * @return $this
     */
    public function withCommands(array $commands = [])
    {
        if (empty($commands)) {
            $commands = [$this->machine->path('Console/Commands')];
        }

        $this->machine->afterResolving(ConsoleKernel::class, function ($kernel) use ($commands) {
            [$commands, $paths] = new Collection($commands)->partition(fn ($command) => class_exists($command));
            [$routes, $paths] = $paths->partition(fn ($path) => is_file($path));

            $this->machine->booted(static function () use ($kernel, $commands, $paths, $routes) {
                $kernel->addCommands($commands->all());
                $kernel->addCommandPaths($paths->all());
                $kernel->addCommandRoutePaths($routes->all());
            });
        });

        return $this;
    }

    /**
     * Register and configure the application's exception handler.
     *
     * @param  callable|null  $using
     * @return $this
     */
    public function withExceptions(?callable $using = null): static
    {
        $this->machine->singleton(
            \Fabricate\Contracts\Debug\ExceptionHandler::class,
            \Fabricate\Core\Exceptions\Handler::class
        );

        if (! is_null($using)) {
            $this->machine->afterResolving(
                \Fabricate\Core\Exceptions\Handler::class,
                fn ($handler) => $using($handler),
            );
        }

        return $this;
    }

    /**
     * Register additional service providers.
     *
     * @param  array  $providers
     * @param  bool  $withBootstrapProviders
     * @return $this
     */
    public function withProviders(array $providers = [], bool $withBootstrapProviders = true)
    {
        RegisterProviders::merge(
            $providers,
            $withBootstrapProviders
                ? $this->machine->getBootstrapProvidersPath()
                : null
        );

        return $this;
    }


    /**
     * Register a callback to be invoked when the application's service providers are registered.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function registered(callable $callback): static
    {
        $this->machine->registered($callback);

        return $this;
    }

    /**
     * Register a callback to be invoked when the application is "booting".
     *
     * @param  callable  $callback
     * @return $this
     */
    public function booting(callable $callback): static
    {
        $this->machine->booting($callback);

        return $this;
    }

    /**
     * Register a callback to be invoked when the application is "booted".
     *
     * @param  callable  $callback
     * @return $this
     */
    public function booted(callable $callback): static
    {
        $this->machine->booted($callback);

        return $this;
    }

    /**
     * Get the application instance.
     *
     * @return Machine
     */
    public function create(): Machine
    {
        return $this->machine;
    }
}
