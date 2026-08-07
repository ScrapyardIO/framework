<?php

namespace Fabricate\NutsAndBolts;

use Closure;
use Fabricate\Chassis\Exceptions\BindingResolutionException;
use Fabricate\Contracts\Chassis\ChassisException;
use Fabricate\Contracts\Chassis\ServiceContainer as ServiceContainerInterface;
use Fabricate\Contracts\Console\CLIMachine;
use Fabricate\Contracts\Core\CachesConfiguration;
use Fabricate\NutsAndBolts\Contracts\DeferrableProvider;
use ReflectionException;

abstract class ServiceProvider
{
    /**
     * The application instance.
     *
     * @var ServiceContainerInterface
     */
    protected ServiceContainerInterface $container;

    /**
     * Every registered booting callback.
     *
     * @var array
     */
    protected array $bootingCallbacks = [];

    /**
     * Every registered booted callback.
     *
     * @var array
     */
    protected array $bootedCallbacks = [];

    /**
     * The paths that should be published.
     *
     * @var array
     */
    public static array $publishes = [];

    /**
     * The paths that should be published by group.
     *
     * @var array
     */
    public static array $publishGroups = [];

    /**
     * The migration paths available for publishing.
     *
     * @var array
     */
    protected static array $publishableMigrationPaths = [];

    /**
     * Commands that should be run during the "optimize" command.
     *
     * @var array<string, string>
     */
    public static array $optimizeCommands = [];

    /**
     * Commands that should be run during the "optimize:clear" command.
     *
     * @var array<string, string>
     */
    public static array $optimizeClearCommands = [];

    /**
     * Commands that should be run during the "reload" command.
     *
     * @var array<string, string>
     */
    public static array $reloadCommands = [];

    /**
     * Create a new service provider instance.
     *
     * @param ServiceContainerInterface $container
     */
    public function __construct(ServiceContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Register a booting callback to be run before the "boot" method is called.
     *
     * @param  Closure  $callback
     * @return void
     */
    public function booting(Closure $callback): void
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a booted callback to be run after the "boot" method is called.
     *
     * @param  Closure  $callback
     * @return void
     */
    public function booted(Closure $callback): void
    {
        $this->bootedCallbacks[] = $callback;
    }

    /**
     * Call the registered booting callbacks.
     *
     * @return void
     */
    public function callBootingCallbacks(): void
    {
        $index = 0;

        while ($index < count($this->bootingCallbacks)) {
            $this->container->call($this->bootingCallbacks[$index]);

            $index++;
        }
    }

    /**
     * Call the registered booted callbacks.
     *
     * @return void
     */
    public function callBootedCallbacks(): void
    {
        $index = 0;

        while ($index < count($this->bootedCallbacks)) {
            $this->container->call($this->bootedCallbacks[$index]);

            $index++;
        }
    }

    /**
     * Merge the given configuration with the existing configuration.
     *
     * @param string $path
     * @param string $key
     * @return void
     * @throws BindingResolutionException|ReflectionException
     */
    protected function mergeConfigFrom(string $path, string $key): void
    {
        if (! ($this->container instanceof CachesConfiguration && $this->container->configurationIsCached())) {
            $config = $this->container->make('config');

            $config->set($key, array_merge(
                require $path, $config->get($key, [])
            ));
        }
    }

    /**
     * Replace the given configuration with the existing configuration recursively.
     *
     * @param string $path
     * @param string $key
     * @return void
     * @throws BindingResolutionException|ReflectionException
     */
    protected function replaceConfigRecursivelyFrom(string $path, string $key): void
    {
        if (! ($this->container instanceof CachesConfiguration && $this->container->configurationIsCached())) {
            $config = $this->container->make('config');

            $config->set($key, array_replace_recursive(
                require $path, $config->get($key, [])
            ));
        }
    }

    /**
     * Register database migration paths.
     *
     * @param array|string $paths
     * @return void
     * @throws BindingResolutionException
     */
    protected function loadMigrationsFrom(array|string $paths): void
    {
        $this->callAfterResolving('migrator', function ($migrator) use ($paths) {
            foreach ((array) $paths as $path) {
                $migrator->path($path);
            }
        });
    }

    /**
     * Set up an after-resolving listener, or fire immediately if already resolved.
     *
     * @param string $name
     * @param callable $callback
     * @return void
     * @throws BindingResolutionException
     */
    protected function callAfterResolving(string $name, callable $callback): void
    {
        $this->container->afterResolving($name, $callback);

        if ($this->container->resolved($name)) {
            $callback($this->container->make($name), $this->container);
        }
    }

    /**
     * Register migration paths to be published by the publish command.
     *
     * @param  array  $paths
     * @param mixed|null $groups
     * @return void
     */
    protected function publishesMigrations(array $paths, mixed $groups = null): void
    {
        $this->publishes($paths, $groups);

        if ($this->container->config->get('database.migrations.update_date_on_publish', false)) {
            static::$publishableMigrationPaths = array_unique(array_merge(static::$publishableMigrationPaths, array_keys($paths)));
        }
    }

    /**
     * Register paths to be published by the publish command.
     *
     * @param  array  $paths
     * @param mixed|null $groups
     * @return void
     */
    protected function publishes(array $paths, mixed $groups = null): void
    {
        $this->ensurePublishArrayInitialized($class = static::class);

        static::$publishes[$class] = array_merge(static::$publishes[$class], $paths);

        foreach ((array) $groups as $group) {
            $this->addPublishGroup($group, $paths);
        }
    }

    /**
     * Ensure the publish-array for the service provider is initialized.
     *
     * @param string $class
     * @return void
     */
    protected function ensurePublishArrayInitialized(string $class): void
    {
        if (! array_key_exists($class, static::$publishes)) {
            static::$publishes[$class] = [];
        }
    }

    /**
     * Add a publish-group / tag to the service provider.
     *
     * @param string $group
     * @param array $paths
     * @return void
     */
    protected function addPublishGroup(string $group, array $paths): void
    {
        if (! array_key_exists($group, static::$publishGroups)) {
            static::$publishGroups[$group] = [];
        }

        static::$publishGroups[$group] = array_merge(
            static::$publishGroups[$group], $paths
        );
    }

    /**
     * Get the paths to publish.
     *
     * @param string|null $provider
     * @param string|null $group
     * @return array
     */
    public static function pathsToPublish(?string $provider = null, ?string $group = null): array
    {
        if (! is_null($paths = static::pathsForProviderOrGroup($provider, $group))) {
            return $paths;
        }

        return new Collection(static::$publishes)->reduce(function ($paths, $p) {
            return array_merge($paths, $p);
        }, []);
    }

    /**
     * Get the paths for the provider or group (or both).
     *
     * @param string|null $provider
     * @param string|null $group
     * @return array
     */
    protected static function pathsForProviderOrGroup(?string $provider, ?string $group): array
    {
        if ($provider && $group) {
            return static::pathsForProviderAndGroup($provider, $group);
        } elseif ($group && array_key_exists($group, static::$publishGroups)) {
            return static::$publishGroups[$group];
        } elseif ($provider && array_key_exists($provider, static::$publishes)) {
            return static::$publishes[$provider];
        } elseif ($group || $provider) {
            return [];
        }

        return [];
    }

    /**
     * Get the paths for the provider and group.
     *
     * @param string $provider
     * @param string $group
     * @return array
     */
    protected static function pathsForProviderAndGroup(string $provider, string $group): array
    {
        if (! empty(static::$publishes[$provider]) && ! empty(static::$publishGroups[$group])) {
            return array_intersect_key(static::$publishes[$provider], static::$publishGroups[$group]);
        }

        return [];
    }

    /**
     * Get the service providers available for publishing.
     *
     * @return array
     */
    public static function publishableProviders(): array
    {
        return array_keys(static::$publishes);
    }

    /**
     * Get the migration paths available for publishing.
     *
     * @return array
     */
    public static function publishableMigrationPaths(): array
    {
        return static::$publishableMigrationPaths;
    }

    /**
     * Get the groups available for publishing.
     *
     * @return array
     */
    public static function publishableGroups(): array
    {
        return array_keys(static::$publishGroups);
    }

    /**
     * Register the package's custom Workshop commands.
     *
     * @param  mixed  $commands
     * @return void
     */
    public function commands(mixed $commands): void
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        /** @var CLIMachine $cli_machine */
        $cli_machine = $this->container->cliMachine();

        $cli_machine::starting(function ($workshop) use ($commands) {
            $workshop->resolveCommands($commands);
        });
    }

    /**
     * Register commands that should run on "optimize" or "optimize:clear".
     *
     * @param  string|null  $optimize
     * @param  string|null  $clear
     * @param  string|null  $key
     * @return void
     */
    protected function optimizes(?string $optimize = null, ?string $clear = null, ?string $key = null): void
    {
        $key = $this->getProviderKey($key);

        if ($optimize) {
            static::$optimizeCommands[$key] = $optimize;
        }

        if ($clear) {
            static::$optimizeClearCommands[$key] = $clear;
        }
    }

    /**
     * Register commands that should run on "reload".
     *
     * @param  string|null  $reload
     * @param  string|null  $key
     * @return void
     */
    protected function reloads(?string $reload, ?string $key = null): void
    {
        $key = $this->getProviderKey($key);

        static::$reloadCommands[$key] = $reload;
    }

    /**
     * Get a short descriptive key for the current service provider.
     *
     * @param  string|null  $key
     * @return string
     */
    protected function getProviderKey(?string $key = null): string
    {
        $key ??= (string) Str::of(get_class($this))
            ->classBasename()
            ->before('ServiceProvider')
            ->kebab()
            ->lower()
            ->trim();

        if (empty($key)) {
            $key = class_basename(get_class($this));
        }

        return $key;
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Get the events that trigger this service provider to register.
     *
     * @return array
     */
    public function when(): array
    {
        return [];
    }

    /**
     * Determine if the provider is deferred.
     *
     * @return bool
     */
    public function isDeferred(): bool
    {
        return $this instanceof DeferrableProvider;
    }

    // @todo - this needs to be moved
    /**
     * Add the given provider to the application's provider bootstrap file.
     *
     * @param  string  $provider
     * @param  string|null  $path
     * @return bool
     */
    public static function addProviderToBootstrapFile(string $provider, ?string $path = null): bool
    {
        $path ??= app()->getBootstrapProvidersPath();

        if (! file_exists($path)) {
            return false;
        }

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }

        $providers = new Collection(require $path)
            ->merge([$provider])
            ->unique()
            ->sort()
            ->values()
            ->map(fn ($p) => '    '.$p.'::class,')
            ->implode(PHP_EOL);

        $content = '<?php

return [
'.$providers.'
];';

        file_put_contents($path, $content.PHP_EOL);

        return true;
    }

    /**
     * Remove a provider from the application's provider bootstrap file.
     *
     * @param  string|array  $providersToRemove
     * @param  string|null  $path
     * @param  bool  $strict
     * @return bool
     * @throws ReflectionException|ChassisException
     */
    public static function removeProviderFromBootstrapFile(string|array $providersToRemove, ?string $path = null, bool $strict = false): bool
    {
        $path ??= app()->getBootstrapProvidersPath();

        if (! file_exists($path)) {
            return false;
        }

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }

        $providersToRemove = Arr::wrap($providersToRemove);

        $providers = new Collection(require $path)
            ->unique()
            ->sort()
            ->values()
            ->when(
                $strict,
                static fn (Collection $providerCollection) => $providerCollection->reject(fn (string $p) => in_array($p, $providersToRemove, true)),
                static fn (Collection $providerCollection) => $providerCollection->reject(fn (string $p) => Str::contains($p, $providersToRemove))
            )
            ->map(fn ($p) => '    '.$p.'::class,')
            ->implode(PHP_EOL);

        $content = '<?php

return [
'.$providers.'
];';

        file_put_contents($path, $content.PHP_EOL);

        return true;
    }
}