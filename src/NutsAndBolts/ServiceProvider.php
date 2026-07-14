<?php

namespace ScrapyardIO\NutsAndBolts;

use BareMetal\Console\Machine as Workshop;
use BareMetal\Contracts\Core\CachesConfiguration;
use BareMetal\Contracts\Support\DeferrableProvider;
use ReflectionException;
use BareMetal\Contracts\Chassis\BindingResolutionException;
use BareMetal\Contracts\Chassis\CircularDependencyException;
use BareMetal\Contracts\Core\Machine;
use ScrpayardIO\NutsAndBolts\Collection;

abstract class ServiceProvider
{
    /**
     * The application instance.
     */
    protected Machine $app;

    /**
     * Every registered "booting" callback.
     */
    protected array $booting_callbacks = [];

    /**
     *Every registered "booted" callback.
     */
    protected array $booted_callbacks = [];

    /**
     * The paths that should be published.
     */
    public static array $publishes = [];

    /**
     * The paths that should be published by group.
     */
    public static array $publish_groups = [];

    /**
     * The migration paths available for publishing.
     */
    protected static array $publishable_migration_paths = [];

    /**
     * Commands that should be run during the "optimize" command.
     */
    public static array $optimize_commands = [];

    /**
     * Commands that should be run during the "optimize:clear" command.
     */
    public static array $optimize_clear_commands = [];

    /**
     * Commands that should be run during the "reload" command.
     */
    public static array $reload_commands = [];

    /**
     * Create a new service provider instance.
     */
    public function __construct(Machine $app)
    {
        $this->app = $app;
    }

    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Register a booting callback to be run before the "boot" method is called.
     */
    public function booting(callable $callback): void
    {
        $this->booting_callbacks[] = $callback;
    }

    /**
     * Register a booted callback to be run after the "boot" method is called.
     */
    public function booted(callable $callback): void
    {
        $this->booted_callbacks[] = $callback;
    }

    /**
     * Call the registered booting callbacks.
     */
    public function callBootingCallbacks(): void
    {
        $index = 0;

        while ($index < count($this->booting_callbacks)) {
            $this->app->call($this->booting_callbacks[$index]);

            $index++;
        }
    }

    /**
     * Call the registered booted callbacks.
     */
    public function callBootedCallbacks(): void
    {
        $index = 0;

        while ($index < count($this->booted_callbacks)) {
            $this->app->call($this->booted_callbacks[$index]);

            $index++;
        }
    }

    /**
     * Merge the given configuration with the existing configuration.
     * @throws BindingResolutionException
     */
    protected function mergeConfigFrom(string $path, string $key): void
    {
        if (! ($this->app instanceof CachesConfiguration && $this->app->configurationIsCached())) {
            $config = $this->app->make('config');

            $config->set($key, array_merge(
                require $path, $config->get($key, [])
            ));
        }
    }

    /**
     * Replace the given configuration with the existing configuration recursively.
     * @throws BindingResolutionException
     */
    protected function replaceConfigRecursivelyFrom(string $path, string $key): void
    {
        if (! ($this->app instanceof CachesConfiguration && $this->app->configurationIsCached())) {
            $config = $this->app->make('config');

            $config->set($key, array_replace_recursive(
                require $path, $config->get($key, [])
            ));
        }
    }


    /**
     * Register database migration paths.
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
     * @throws BindingResolutionException
     */
    protected function callAfterResolving(string $name, callable $callback): void
    {
        $this->app->afterResolving($name, $callback);

        if ($this->app->resolved($name)) {
            $callback($this->app->make($name), $this->app);
        }
    }

    /**
     * Register migration paths to be published by the publish command.
     */
    protected function publishesMigrations(array $paths, mixed $groups = null): void
    {
        $this->publishes($paths, $groups);

        if ($this->app->config->get('database.migrations.update_date_on_publish', false)) {
            static::$publishable_migration_paths = array_unique(array_merge(static::$publishable_migration_paths, array_keys($paths)));
        }
    }

    /**
     * Register paths to be published by the publish command.
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
     */
    protected function ensurePublishArrayInitialized(string $class): void
    {
        if (! array_key_exists($class, static::$publishes)) {
            static::$publishes[$class] = [];
        }
    }

    /**
     * Add a publish-group / tag to the service provider.
     */
    protected function addPublishGroup(string $group, array $paths): void
    {
        if (! array_key_exists($group, static::$publish_groups)) {
            static::$publish_groups[$group] = [];
        }

        static::$publish_groups[$group] = array_merge(
            static::$publish_groups[$group], $paths
        );
    }

    /**
     * Get the paths to publish.
     */
    public static function pathsToPublish(?string $provider = null, ?string $group = null): array
    {
        if (! is_null($paths = static::pathsForProviderOrGroup($provider, $group))) {
            return $paths;
        }

        return (new Collection(static::$publishes))->reduce(function ($paths, $p) {
            return array_merge($paths, $p);
        }, []);
    }

    /**
     * Get the paths for the provider or group (or both).
     *
     * @param  string|null  $provider
     * @param  string|null  $group
     * @return array
     */
    protected static function pathsForProviderOrGroup(?string $provider, ?string $group): array
    {
        if ($provider && $group) {
            return static::pathsForProviderAndGroup($provider, $group);
        } elseif ($group && array_key_exists($group, static::$publish_groups)) {
            return static::$publish_groups[$group];
        } elseif ($provider && array_key_exists($provider, static::$publishes)) {
            return static::$publishes[$provider];
        } elseif ($group || $provider) {
            return [];
        }

        return [];
    }

    /**
     * Get the paths for the provider and group.
     */
    protected static function pathsForProviderAndGroup(string $provider, string $group): array
    {
        if (! empty(static::$publishes[$provider]) && ! empty(static::$publish_groups[$group])) {
            return array_intersect_key(static::$publishes[$provider], static::$publish_groups[$group]);
        }

        return [];
    }

    /**
     * Get the service providers available for publishing.
     */
    public static function publishableProviders(): array
    {
        return array_keys(static::$publishes);
    }

    /**
     * Get the migration paths available for publishing.
     */
    public static function publishableMigrationPaths(): array
    {
        return static::$publishable_migration_paths;
    }

    /**
     * Get the groups available for publishing.
     *
     * @return array
     */
    public static function publishableGroups(): array
    {
        return array_keys(static::$publish_groups);
    }

    /**
     * Register the package's custom Workshop commands.
     */
    public function commands(mixed $commands): void
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        Workshop::starting(function ($artisan) use ($commands) {
            $artisan->resolveCommands($commands);
        });
    }

    /**
     * Register commands that should run on "optimize" or "optimize:clear".
     */
    protected function optimizes(?string $optimize = null, ?string $clear = null, ?string $key = null): void
    {
        $key = $this->getProviderKey($key);

        if ($optimize) {
            static::$optimize_commands[$key] = $optimize;
        }

        if ($clear) {
            static::$optimize_clear_commands[$key] = $clear;
        }
    }

    /**
     * Register commands that should run on "reload".
     */
    protected function reloads(?string $reload, ?string $key = null): void
    {
        $key = $this->getProviderKey($key);

        static::$reload_commands[$key] = $reload;
    }

    /**
     * Get a short descriptive key for the current service provider.
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
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Get the events that trigger this service provider to register.
     */
    public function when(): array
    {
        return [];
    }

    /**
     * Determine if the provider is deferred.
     */
    public function isDeferred(): bool
    {
        return $this instanceof DeferrableProvider;
    }

    /**
     * Get the default providers for a Laravel application.
     */
    public static function defaultProviders(): DefaultProviders
    {
        return new DefaultProviders;
    }

    /**
     * Add the given provider to the application's provider bootstrap file.
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
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

        $providers = (new Collection(require $path))
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
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
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

        $providers = (new Collection(require $path))
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
