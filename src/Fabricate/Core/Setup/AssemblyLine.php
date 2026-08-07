<?php

namespace Fabricate\Core\Setup;

use Fabricate\Contracts\Console\CLIKernel as ConsoleKernel;
use Fabricate\Core\Bootstrap\RegisterProviders;
use Fabricate\Core\Machine;
use Fabricate\Core\Providers\EventServiceProvider as AppEventServiceProvider;
use Fabricate\NutsAndBolts\Collection;
use ReflectionException;

class AssemblyLine
{
    /**
     * The service provider that are marked for registration.
     *
     * @var array
     */
    protected array $pendingProviders = [];


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
            \Fabricate\Contracts\Console\CLIKernel::class,
            \Fabricate\Core\Console\ConsoleKernel::class,
        );

        $this->machine->singleton(
            \Fabricate\Contracts\Sketches\SketchKernel::class,
            \Fabricate\Sketches\Runner\SketchKernel::class,
        );

        return $this;
    }

    /**
     * Register additional service providers.
     *
     * @param  array  $providers
     * @param  bool  $withBootstrapProviders
     * @return $this
     */
    public function withProviders(array $providers = [], bool $withBootstrapProviders = true): static
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
     * Register and configure the application's exception handler.
     *
     * @param callable|null $using
     * @return $this
     * @throws ReflectionException
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
     * Register additional Artisan commands with the application.
     *
     * @param  array  $commands
     * @return $this
     */
    public function withCommands(array $commands = []): static
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
            });
        });

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