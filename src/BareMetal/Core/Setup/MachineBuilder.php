<?php

namespace BareMetal\Core\Setup;

use ReflectionException;
use BareMetal\Core\Machine;
use ScrpayardIO\NutsAndBolts\Collection;
use BareMetal\Console\Machine as Workshop;
use BareMetal\Core\Bootstrap\RegisterProviders;
use BareMetal\Contracts\Console\Kernel as ConsoleKernel;
use BareMetal\Contracts\Chassis\BindingResolutionException;
use BareMetal\Contracts\Chassis\CircularDependencyException;
use BareMetal\Core\Support\Providers\EventServiceProvider as AppEventServiceProvider;

class MachineBuilder
{
    /**
     * The service provider that are marked for registration.
     */
    protected array $pending_providers = [];

    /**
     * Create a new application builder instance.
     */
    public function __construct(protected Machine $app)
    {
    }

    /**
     * Register the standard kernel classes for the application.
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     */
    public function withKernels(): static
    {
        $this->app->singleton(
            \BareMetal\Contracts\Console\Kernel::class,
            \BareMetal\Core\Console\ConsoleKernel::class,
        );

        return $this;
    }

    /**
     * Register additional service providers.

     */
    public function withProviders(array $providers = [], bool $with_bootstrap_providers = true): static
    {
        RegisterProviders::merge(
            $providers,
            $with_bootstrap_providers
                ? $this->app->getBootstrapProvidersPath()
                : null
        );

        return $this;
    }

    /**
     * Register the core event service provider for the application.
     */
    public function withEvents(iterable|bool $discover = true): static
    {
        if (is_iterable($discover)) {
            AppEventServiceProvider::setEventDiscoveryPaths($discover);
        }

        if ($discover === false) {
            AppEventServiceProvider::disableEventDiscovery();
        }

        if (! isset($this->pending_providers[AppEventServiceProvider::class])) {
            $this->app->booting(function () {
                $this->app->register(AppEventServiceProvider::class);
            });
        }

        $this->pending_providers[AppEventServiceProvider::class] = true;

        return $this;
    }

    /**
     * Register the broadcasting services for the application.
     */
    public function withBroadcasting(string $channels, array $attributes = []): static
    {
        $this->app->booted(function () use ($channels, $attributes) {
            if (file_exists($channels)) {
                require $channels;
            }
        });

        return $this;
    }

    /**
     * Register the global middleware, middleware groups, and middleware aliases for the application.
     */
    public function withMiddleware(?callable $callback = null): static
    {
        $this->app->afterResolving(ConsoleKernel::class, function () use ($callback) {
            if (! is_null($callback)) {
                $callback(new Middleware);
            }
        });

        return $this;
    }

    /**
     * Register additional Workshop commands with the application.
     */
    public function withCommands(array $commands = []): static
    {
        if (empty($commands)) {
            $commands = [$this->app->path('Console/Commands')];
        }

        $this->app->afterResolving(ConsoleKernel::class, function ($kernel) use ($commands) {
            [$commands, $paths] = (new Collection($commands))->partition(fn ($command) => class_exists($command));
            [$routes, $paths] = $paths->partition(fn ($path) => is_file($path));

            $this->app->booted(static function () use ($kernel, $commands, $paths, $routes) {
                $kernel->addCommands($commands->all());
                $kernel->addCommandPaths($paths->all());
                $kernel->addCommandRoutePaths($routes->all());
            });
        });

        return $this;
    }

    /**
     * Register the scheduled tasks for the application.
     */
    public function withSchedule(callable $callback): static
    {
        Workshop::starting(function () use ($callback) {
            /*$this->app->afterResolving(Schedule::class, fn ($schedule) => $callback($schedule));

            if ($this->app->resolved(Schedule::class)) {
                $callback($this->app->make(Schedule::class));
            }*/
        });

        return $this;
    }

    /**
     * Register and configure the application's exception handler.
     * @throws ReflectionException
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     */
    public function withExceptions(?callable $using = null): static
    {
        $this->app->singleton(
            \BareMetal\Contracts\Debug\ExceptionHandler::class,
            \BareMetal\Core\Exceptions\Handler::class
        );

        if ($using !== null) {
            $this->app->afterResolving(
                \BareMetal\Core\Exceptions\Handler::class,
                fn ($handler) => $using(new Exceptions($handler)),
            );
        }

        return $this;
    }

    /**
     * Register an array of container bindings to be bound when the application is booting.
     */
    public function withBindings(array $bindings): static
    {
        return $this->registered(function ($app) use ($bindings) {
            foreach ($bindings as $abstract => $concrete) {
                $app->bind($abstract, $concrete);
            }
        });
    }

    /**
     * Register an array of singleton container bindings to be bound when the application is booting.
     */
    public function withSingletons(array $singletons): static
    {
        return $this->registered(function ($app) use ($singletons) {
            foreach ($singletons as $abstract => $concrete) {
                if (is_string($abstract)) {
                    $app->singleton($abstract, $concrete);
                } else {
                    $app->singleton($concrete);
                }
            }
        });
    }

    /**
     * Register an array of scoped singleton container bindings to be bound when the application is booting.
     */
    public function withScopedSingletons(array $scoped_singletons): static
    {
        return $this->registered(function ($app) use ($scoped_singletons) {
            foreach ($scoped_singletons as $abstract => $concrete) {
                if (is_string($abstract)) {
                    $app->scoped($abstract, $concrete);
                } else {
                    $app->scoped($concrete);
                }
            }
        });
    }

    /**
     * Register a callback to be invoked when the application's service providers are registered.
     */
    public function registered(callable $callback): static
    {
        $this->app->registered($callback);

        return $this;
    }

    /**
     * Register a callback to be invoked when the application is "booting".
     */
    public function booting(callable $callback): static
    {
        $this->app->booting($callback);

        return $this;
    }

    /**
     * Register a callback to be invoked when the application is "booted".
     */
    public function booted(callable $callback): static
    {
        $this->app->booted($callback);

        return $this;
    }

    /**
     * Get the application instance.
     */
    public function create(): Machine
    {
        return $this->app;
    }
}
