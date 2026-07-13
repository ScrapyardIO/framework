<?php

namespace BareMetal\Core;

use Closure;
use RuntimeException;
use Illuminate\Support\Env;
use Illuminate\Support\Str;
use Illuminate\Container\Container;
use Psr\Container\ContainerInterface;
use Illuminate\Filesystem\Filesystem;
use GPIO\Contracts\Common\GPIOLibrary;
use BareMetal\Core\Setup\ScrapyardBuilder;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\InputInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use BareMetal\Contracts\Core\Application as ScrapyardContract;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;

class Scrapyard extends Container implements ScrapyardContract
{
    /**
     * The ScrapyardIO framework version.
     */
    const string VERSION = '0.5.1';

    /**
     * The base path for the ScrapyardIO installation.
     */
    protected string $base_path;

    /**
     * The custom bootstrap path defined by the developer.
     */
    protected ?string $bootstrap_path = null;

    /**
     * The custom configuration path defined by the developer.
     */
    protected ?string $config_path = null;

    /**
     * The custom database path defined by the developer.
     */
    protected ?string $database_path = null;

    /**
     * The custom storage path defined by the developer.
     */
    protected ?string $storage_path = null;

    /**
     * The custom environment path defined by the developer.
     */
    protected ?string $environment_path = null;

    /**
     * The environment file to load during bootstrapping.
     */
    protected string $environment_file = '.env';

    /**
     * Indicates if the application is running in the console.
     */
    protected ?bool $is_running_in_console = null;

    /**
     * The application environment.
     */
    protected string $environment = 'production';

    /**
     * Indicates if debug mode is enabled.
     */
    protected bool $debug_mode_enabled = false;

    /**
     * The application locale.
     */
    protected string $locale = 'en';

    /**
     * The application namespace.
     */
    protected ?string $namespace = null;

    /**
     * Indicates if the application has been bootstrapped before.
     */
    protected bool $has_been_bootstrapped = false;

    /**
     * Indicates if the application has "booted".
     */
    protected bool $booted = false;

    /**
     * The array of booting callbacks.
     *
     * @var callable[]
     */
    protected array $booting_callbacks = [];

    /**
     * The array of booted callbacks.
     *
     * @var callable[]
     */
    protected array $booted_callbacks = [];

    /**
     * The array of terminating callbacks.
     *
     * @var array<int, callable|string>
     */
    protected array $terminating_callbacks = [];

    /**
     * Every registered service provider.
     *
     * @var array<string, ServiceProvider>
     */
    protected array $service_providers = [];

    /**
     * The names of the loaded service providers.
     *
     * @var array<string, bool>
     */
    protected array $loaded_providers = [];

    /**
     * The deferred services and their providers.
     *
     * @var array<string, class-string>
     */
    protected array $deferred_services = [];

    /**
     * The prefixes of absolute cache paths for use during normalization.
     */
    protected array $absolute_cache_path_prefixes = ['/', '\\'];

    public function __construct($base_path = null)
    {
        if ($base_path) {
            $this->setBasePath($base_path);
        }

        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerCoreContainerAliases();
    }

    public static function setup(?string $basePath = null): ScrapyardBuilder
    {
        return (new ScrapyardBuilder(new static($basePath)))
            ->withKernels()
            //->withEvents()
            //->withCommands()
            ->withProviders();
    }

    /**
     * Register every base service provider.
     */
    protected function registerBaseServiceProviders(): void
    {
        //$this->register(new EventServiceProvider($this));
        //$this->register(new LogServiceProvider($this));
        //$this->register(new ContextServiceProvider($this));
    }

    /**
     * Set the base path for the application.
     */
    public function setBasePath(string $base_path): static
    {
        $this->base_path = rtrim($base_path, '\/');

        //$this->bindPathsInContainer();

        return $this;
    }

    /**
     * @throws BindingResolutionException
     */
    public function handleCommand(InputInterface $input): int
    {
        $kernel = $this->make(ConsoleKernelContract::class);

        $status = $kernel->handle(
            $input,
            new ConsoleOutput
        );

        $kernel->terminate($input, $status);

        return $status;
    }

    /**
     * Resolve the given type from the container.
     *
     * @template TClass of object
     *
     * @param  string|class-string<TClass>  $abstract
     * @param  array  $parameters
     * @return ($abstract is class-string<TClass> ? TClass : mixed)
     *
     * @throws BindingResolutionException
     */
    public function make($abstract, array $parameters = [])
    {
        //$this->loadDeferredProviderIfNeeded($abstract = $this->getAlias($abstract));

        return parent::make($abstract, $parameters);
    }

    /**
     * Determine if the application has been bootstrapped before.
     */
    public function hasBeenBootstrapped(): bool
    {
        return $this->has_been_bootstrapped;
    }

    /**
     * Get the version number of the application.
     */
    public function version(): string
    {
        return static::VERSION;
    }

    /**
     * Get the base path of the ScrapyardIO installation.
     */
    public function basePath(string $path = ''): string
    {
        return $this->joinPaths($this->base_path, $path);
    }

    /**
     * Join the given paths together.
     */
    public function joinPaths(string $base_path, string $path = ''): string
    {
        return join_paths($base_path, $path);
    }

    /**
     * Get the path to the bootstrap directory.
     */
    public function bootstrapPath(string $path = ''): string
    {
        return $this->joinPaths($this->bootstrap_path ?: $this->basePath('bootstrap'), $path);
    }

    /**
     * Get the path to the application configuration files.
     */
    public function configPath(string $path = ''): string
    {
        return $this->joinPaths($this->config_path ?: $this->basePath('config'), $path);
    }

    /**
     * Get the path to the database directory.
     */
    public function databasePath(string $path = ''): string
    {
        return $this->joinPaths($this->database_path ?: $this->basePath('database'), $path);
    }

    /**
     * Get the path to the storage directory.
     */
    public function storagePath(string $path = ''): string
    {
        if (isset($_ENV['SCRAPYARD_STORAGE_PATH'])) {
            return $this->joinPaths($this->storage_path ?: $_ENV['SCRAPYARD_STORAGE_PATH'], $path);
        }

        if (isset($_SERVER['SCRAPYARD_STORAGE_PATH'])) {
            return $this->joinPaths($this->storage_path ?: $_SERVER['SCRAPYARD_STORAGE_PATH'], $path);
        }

        return $this->joinPaths($this->storage_path ?: $this->basePath('storage'), $path);
    }

    /**
     * Get or check the current application environment.
     */
    public function environment(string|array ...$environments): string|bool
    {
        if ($environments !== []) {
            $patterns = is_array($environments[0]) ? $environments[0] : $environments;

            foreach ($patterns as $pattern) {
                $pattern = (string) $pattern;

                if ($pattern === '*' || $pattern === $this->environment) {
                    return true;
                }

                $regex = '#^'.str_replace('\*', '.*', preg_quote($pattern, '#')).'\z#';

                if (preg_match($regex, $this->environment) === 1) {
                    return true;
                }
            }

            return false;
        }

        return $this->environment;
    }

    /**
     * Detect the application's current environment.
     */
    public function detectEnvironment(Closure $callback): string
    {
        $args = $this->runningInConsole() && isset($_SERVER['argv'])
            ? $_SERVER['argv']
            : null;

        return $this->environment = $this['env'] = (new EnvironmentDetector)->detect($callback, $args);
    }

    /**
     * Determine if the application is running unit tests.
     */
    public function runningUnitTests(): bool
    {
        return $this->environment === 'testing';
    }

    /**
     * Determine if the application is running with debug mode enabled.
     */
    public function hasDebugModeEnabled(): bool
    {
        if (isset($_ENV['APP_DEBUG'])) {
            return filter_var($_ENV['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($_SERVER['APP_DEBUG'])) {
            return filter_var($_SERVER['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN);
        }

        return $this->debug_mode_enabled;
    }

    /**
     * Register every configured provider.
     *
     * Needs config + provider discovery before this can mirror Laravel.
     */
    public function registerConfiguredProviders(): void
    {
        // TODO: Implement registerConfiguredProviders() method.
    }

    /**
     * Register a service provider with the application.
     * @throws \ReflectionException
     */
    public function register(ServiceProvider|string $provider, bool $force = false): ServiceProvider
    {
        if (($registered = $this->getProvider($provider)) && ! $force) {
            return $registered;
        }

        if (is_string($provider)) {
            $provider = $this->resolveProvider($provider);
        }

        $provider->register();

        if (property_exists($provider, 'bindings')) {
            foreach ($provider->bindings as $key => $value) {
                $this->bind($key, $value);
            }
        }

        if (property_exists($provider, 'singletons')) {
            foreach ($provider->singletons as $key => $value) {
                $key = is_int($key) ? $value : $key;

                $this->singleton($key, $value);
            }
        }

        $this->markAsRegistered($provider);

        if ($this->isBooted()) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    /**
     * Register a deferred provider and service.
     */
    public function registerDeferredProvider(string $provider, ?string $service = null): void
    {
        if ($service) {
            unset($this->deferred_services[$service]);
        }

        $this->register($instance = new $provider($this));

        if (! $this->isBooted()) {
            $this->booting(function () use ($instance) {
                $this->bootProvider($instance);
            });
        }
    }

    /**
     * Resolve a service provider instance from the class name.
     */
    public function resolveProvider(string $provider): ServiceProvider
    {
        return new $provider($this);
    }

    /**
     * Boot the application's service providers.
     */
    public function boot(): void
    {
        if ($this->isBooted()) {
            return;
        }

        $this->fireAppCallbacks($this->booting_callbacks);

        array_walk($this->service_providers, function ($provider) {
            $this->bootProvider($provider);
        });

        $this->booted = true;

        $this->fireAppCallbacks($this->booted_callbacks);
    }

    /**
     * Register a new boot listener.
     */
    public function booting(callable $callback): void
    {
        $this->booting_callbacks[] = $callback;
    }

    /**
     * Register a new "booted" listener.
     */
    public function booted(callable $callback): void
    {
        $this->booted_callbacks[] = $callback;

        if ($this->isBooted()) {
            $callback($this);
        }
    }

    /**
     * Run the given array of bootstrap classes.
     *
     * Event dispatch around each bootstrapper is skipped until withEvents() exists.
     */
    public function bootstrapWith(array $bootstrappers): void
    {
        $this->has_been_bootstrapped = true;

        foreach ($bootstrappers as $bootstrapper) {
            $this->make($bootstrapper)->bootstrap($this);
        }
    }

    /**
     * Get the current application locale.
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Get the application namespace.
     *
     * @throws RuntimeException
     */
    public function getNamespace(): string
    {
        if (! is_null($this->namespace)) {
            return $this->namespace;
        }

        $composer = json_decode(file_get_contents($this->basePath('composer.json')), true);
        $psr4 = $composer['autoload']['psr-4'] ?? [];
        $app_path = realpath($this->basePath('app'));

        foreach ((array) $psr4 as $namespace => $path) {
            foreach ((array) $path as $path_choice) {
                if ($app_path === realpath($this->basePath($path_choice))) {
                    return $this->namespace = $namespace;
                }
            }
        }

        throw new RuntimeException('Unable to detect application namespace.');
    }

    /**
     * Get the registered service provider instances if any exist.
     */
    public function getProviders(ServiceProvider|string $provider): array
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return array_values(array_filter(
            $this->service_providers,
            fn ($value) => $value instanceof $name
        ));
    }

    /**
     * Load and boot every remaining deferred provider.
     */
    public function loadDeferredProviders(): void
    {
        foreach ($this->deferred_services as $service => $provider) {
            $this->loadDeferredProvider($service);
        }

        $this->deferred_services = [];
    }

    /**
     * Set the current application locale.
     *
     * Translator / LocaleUpdated event wiring skipped until those systems exist.
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * Register a terminating callback with the application.
     */
    public function terminating(callable|string $callback): ScrapyardContract
    {
        $this->terminating_callbacks[] = $callback;

        return $this;
    }

    /**
     * Terminate the application.
     */
    public function terminate(): void
    {
        $index = 0;

        while ($index < count($this->terminating_callbacks)) {
            $this->call($this->terminating_callbacks[$index]);

            $index++;
        }
    }

    /**
     * Determine if the application has booted.
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Get the registered service provider instance if it exists.
     */
    public function getProvider(ServiceProvider|string $provider): ?ServiceProvider
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return $this->service_providers[$name] ?? null;
    }

    /**
     * Load the provider for a deferred service.
     */
    public function loadDeferredProvider(string $service): void
    {
        if (! $this->isDeferredService($service)) {
            return;
        }

        $provider = $this->deferred_services[$service];

        if (! isset($this->loaded_providers[$provider])) {
            $this->registerDeferredProvider($provider, $service);
        }
    }

    /**
     * Determine if the given service is a deferred service.
     */
    public function isDeferredService(string $service): bool
    {
        return isset($this->deferred_services[$service]);
    }

    /**
     * Boot the given service provider.
     */
    protected function bootProvider(ServiceProvider $provider): void
    {
        $provider->callBootingCallbacks();

        if (method_exists($provider, 'boot')) {
            $this->call([$provider, 'boot']);
        }

        $provider->callBootedCallbacks();
    }

    /**
     * Mark the given provider as registered.
     */
    protected function markAsRegistered(ServiceProvider $provider): void
    {
        $class = get_class($provider);

        $this->service_providers[$class] = $provider;

        $this->loaded_providers[$class] = true;
    }

    /**
     * Call the application callbacks.
     *
     * @param  callable[]  $callbacks
     */
    protected function fireAppCallbacks(array &$callbacks): void
    {
        $index = 0;

        while ($index < count($callbacks)) {
            $callbacks[$index]($this);

            $index++;
        }
    }

    /**
     * Register the basic bindings into the container.
     */
    protected function registerBaseBindings(): void
    {
        static::setInstance($this);

        $this->instance('app', $this);

        $this->instance(Container::class, $this);

        $this->singleton(PackageManifest::class, fn () => new PackageManifest(
            new Filesystem, $this->basePath(), $this->getCachedPackagesPath()
        ));
    }

    /**
     * Get the path to the cached packages.php file.
     */
    public function getCachedPackagesPath(): string
    {
        return $this->normalizeCachePath('APP_PACKAGES_CACHE', 'cache/packages.php');
    }



    /**
     * Normalize a relative or absolute path to a cache file.
     */
    protected function normalizeCachePath(string $key, string $default): string
    {
        if (is_null($env = Env::get($key))) {
            return $this->bootstrapPath($default);
        }

        return Str::startsWith($env, $this->absolute_cache_path_prefixes)
            ? $env
            : $this->basePath($env);
    }

    /**
     * Register the core class aliases in the container.
     */
    public function registerCoreContainerAliases(): void
    {
        foreach ([
            'app' => [self::class, Container::class, ScrapyardContract::class, ContainerInterface::class],
            'files' => [\Illuminate\Filesystem\Filesystem::class],
            'filesystem' => [\Illuminate\Filesystem\FilesystemManager::class, \Illuminate\Contracts\Filesystem\Factory::class],
            'filesystem.disk' => [\Illuminate\Contracts\Filesystem\Filesystem::class],
            'redis' => [\Illuminate\Redis\RedisManager::class, \Illuminate\Contracts\Redis\Factory::class],
            'redis.connection' => [\Illuminate\Redis\Connections\Connection::class, \Illuminate\Contracts\Redis\Connection::class],
            'io' => [GPIOLibrary::class, GPIOLibrary::class],
        ] as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($key, $alias);
            }
        }
    }

    /**
     * Get the path to the service provider list in the bootstrap directory.
     */
    public function getBootstrapProvidersPath(): string
    {
        return $this->bootstrapPath('providers.php');
    }

    /**
     * Determine if the application is running in the console.
     */
    public function runningInConsole(): bool
    {
        if ($this->is_running_in_console === null) {
            $this->is_running_in_console = Env::get('APP_RUNNING_IN_CONSOLE') ?? (\PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg');
        }

        return $this->is_running_in_console;
    }

    /**
     * Determine if the application configuration is cached.
     * @throws BindingResolutionException
     */
    public function configurationIsCached(): bool
    {
        if ($this->bound('config_loaded_from_cache')) {
            return (bool) $this->make('config_loaded_from_cache');
        }

        return $this->instance('config_loaded_from_cache', is_file($this->getCachedConfigPath()));
    }

    /**
     * Get the path to the configuration cache file.
     */
    public function getCachedConfigPath(): string
    {
        return $this->normalizeCachePath('APP_CONFIG_CACHE', 'cache/config.php');
    }

    /**
     * Get the path to the environment file directory.
     */
    public function environmentPath(): string
    {
        return $this->environment_path ?: $this->base_path;
    }

    /**
     * Set the directory for the environment file.
     */
    public function useEnvironmentPath(string $path): static
    {
        $this->environment_path = $path;

        return $this;
    }

    /**
     * Get the environment file the application is using.
     */
    public function environmentFile(): string
    {
        return $this->environment_file ?: '.env';
    }

    /**
     * Get the fully-qualified path to the environment file.
     */
    public function environmentFilePath(): string
    {
        return $this->environmentPath().DIRECTORY_SEPARATOR.$this->environmentFile();
    }

    /**
     * Set the environment file to be loaded during bootstrapping.
     */
    public function loadEnvironmentFrom(string $file): static
    {
        $this->environment_file = $file;

        return $this;
    }
}
