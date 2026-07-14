<?php

namespace BareMetal\Core;

use RuntimeException;
use BareMetal\Filesystem\Filesystem;
use Composer\Autoload\ClassLoader;
use ReflectionException;
use BareMetal\Chassis\Chassis;
use BareMetal\Contracts\Chassis\BindingResolutionException;
use BareMetal\Contracts\Chassis\CircularDependencyException;
use BareMetal\Contracts\Core\CachesConfiguration;
use BareMetal\Contracts\Core\Machine as MachineContract;
use BareMetal\Core\Setup\MachineBuilder;
use ScrapyardIO\NutsAndBolts\Concerns\Macroable;
use ScrapyardIO\NutsAndBolts\Env;
use ScrapyardIO\NutsAndBolts\ServiceProvider;
use BareMetal\Contracts\Console\Kernel as ConsoleKernelInterface;
use ScrapyardIO\NutsAndBolts\Arr;
use ScrapyardIO\NutsAndBolts\Str;
use ScrpayardIO\NutsAndBolts\Collection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use function BareMetal\Filesystem\join_paths;

class Machine extends Chassis implements MachineContract, CachesConfiguration
{
    use Macroable;

    /**
     * The ScrapyardIO framework version.
     */
    const string VERSION = '0.5.1';

    /**
     * The base path for the Laravel installation.
     */
    protected string $base_path = "";

    /**
     * The array of registered callbacks.
     */
    protected array $registeredCallbacks = [];

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
     */
    protected array $booting_callbacks = [];

    /**
     * The array of booted callbacks.
     */
    protected array $booted_callbacks = [];

    /**
     * The array of terminating callbacks.
     */
    protected array $terminatingCallbacks = [];

    /**
     * Every registered service provider.
     */
    protected array $serviceProviders = [];

    /**
     * The names of the loaded service providers.

     */
    protected array $loaded_providers = [];

    /**
     * The deferred services and their providers.
     */
    protected array $deferred_services = [];

    /**
     * The custom bootstrap path defined by the developer.
     */
    protected string $bootstrap_path = "";

    /**
     * The custom application path defined by the developer.
     */
    protected string $app_path = "";

    /**
     * The custom configuration path defined by the developer.
     */
    protected string $config_path = "";

    /**
     * The custom database path defined by the developer.
     */
    protected string $database_path = "";

    /**
     * The custom storage path defined by the developer.
     */
    protected string $storage_path = "";

    /**
     * The custom environment path defined by the developer.
     */
    protected string $environment_path = "";

    /**
     * The environment file to load during bootstrapping.
     */
    protected string $environment_file = '.env';

    /**
     * The current application environment.
     */
    protected string $environment = 'production';

    /**
     * Indicates if the application is running in the console.
     */
    protected ?bool $is_running_in_console = null;

    /**
     * The application namespace.
     */
    protected ?string $namespace = null;

    /**
     * Indicates if the framework's base configuration should be merged.
     */
    protected bool $merge_framework_configuration = true;

    /**
     * The prefixes of absolute cache paths for use during normalization.
     */
    protected array $absolute_cache_path_prefixes = ['/', '\\'];

    /**
     * Create a new Illuminate application instance.
     * @throws BindingResolutionException
     * @throws CircularDependencyException|ReflectionException
     */
    public function __construct(?string $base_path = null)
    {
        if ($base_path) {
            $this->setBasePath($base_path);
        }

        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerCoreContainerAliases();
    }


    /**
     * Get the version number of the application.
     */
    public function version(): string
    {
        return static::VERSION;
    }

    /**
     * Set the base path for the application.
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     */
    public function setBasePath(string $base_path): static
    {
        $this->base_path = rtrim($base_path, '\/');

        $this->bindPathsInContainer();

        return $this;
    }

    /**
     * Determine if the application configuration is cached.
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     */
    public function configurationIsCached(): bool
    {
        if ($this->bound('config_loaded_from_cache')) {
            return (bool) $this->make('config_loaded_from_cache');
        }

        return $this->instance('config_loaded_from_cache', is_file($this->getCachedConfigPath()));
    }

    public function getCachedConfigPath(): string
    {
        return $this->normalizeCachePath('APP_CONFIG_CACHE', 'cache/config.php');
    }

    public function getCachedServicesPath(): string
    {
        return $this->normalizeCachePath('APP_SERVICES_CACHE', 'cache/services.php');
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
    public function joinPaths(string $base_path, string$path = ''): string
    {
        return join_paths($base_path, $path);
    }

    /**
     * Get the path to the bootstrap directory.
     */
    public function bootstrapPath(string $path = ''): string
    {
        return $this->joinPaths($this->bootstrap_path, $path);
    }

    /**
     * Get the path to the application configuration files.
     */
    public function configPath(string $path = ''): string
    {
        return $this->joinPaths($this->config_path ?: $this->basePath('config'), $path);
    }

    public function databasePath(string $path = ''): string
    {
        // TODO: Implement databasePath() method.
    }

    /**
     * Get the path to the storage directory.
     */
    public function storagePath(string $path = ''): string
    {
        if (isset($_ENV['SCRAPYARDIO_STORAGE_PATH'])) {
            return $this->joinPaths($this->storage_path ?: $_ENV['SCRAPYARDIO_STORAGE_PATH'], $path);
        }

        if (isset($_SERVER['SCRAPYARDIO_STORAGE_PATH'])) {
            return $this->joinPaths($this->storage_path ?: $_SERVER['SCRAPYARDIO_STORAGE_PATH'], $path);
        }

        return $this->joinPaths($this->storage_path ?: $this->basePath('storage'), $path);
    }

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

    public function runningInConsole(): bool
    {
        if ($this->is_running_in_console === null) {
            $this->is_running_in_console = Env::get('APP_RUNNING_IN_CONSOLE') ?? (\PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg');
        }

        return $this->is_running_in_console;
    }

    public function runningUnitTests(): bool
    {
        return Env::get('APP_ENV') === 'testing';
    }

    public function hasDebugModeEnabled(): bool
    {
        // TODO: Implement hasDebugModeEnabled() method.
    }

    /**
     * Register every configured provider.
     */
    public function registerConfiguredProviders(): void
    {
        $providers = (new Collection($this->make('config')->get('scrapyard-io.providers')))
            ->partition(fn ($provider) => str_starts_with($provider, 'BareMetal\\'));

        $providers->splice(1, 0, [$this->make(PackageManifest::class)->providers()]);

        (new ProviderRepository($this, new Filesystem, $this->getCachedServicesPath()))
            ->load($providers->collapse()->toArray());

        $this->fireAppCallbacks($this->registeredCallbacks);
    }

    /**
     * Add an array of services to the application's deferred services.
     */
    public function addDeferredServices(array $services): void
    {
        $this->deferred_services = array_merge($this->deferred_services, $services);
    }

    /**
     * Register a service provider with the application.
     */
    public function register(string|ServiceProvider $provider, bool $force = false): ServiceProvider
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
     * Get the registered service provider instance if it exists.
     */
    public function getProvider(string|ServiceProvider $provider): ?ServiceProvider
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return $this->serviceProviders[$name] ?? null;
    }

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

    public function resolveProvider(string $provider): ServiceProvider
    {
        return new $provider($this);
    }

    /**
     * Mark the given provider as registered.
     */
    protected function markAsRegistered(ServiceProvider $provider): void
    {
        $class = get_class($provider);

        $this->serviceProviders[$class] = $provider;

        $this->loaded_providers[$class] = true;
    }

    /**
     * Determine if the application has booted.
     */
    public function isBooted(): bool
    {
        return $this->booted;
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

        array_walk($this->serviceProviders, function ($p) {
            $this->bootProvider($p);
        });

        $this->booted = true;

        $this->fireAppCallbacks($this->booted_callbacks);
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
     * Call the booting callbacks for the application.
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
     * Run the given array of bootstrap classes.
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     */
    public function bootstrapWith(array $bootstrappers): void
    {
        $this->has_been_bootstrapped = true;

        foreach ($bootstrappers as $bootstrapper) {
            $this['events']->dispatch('bootstrapping: '.$bootstrapper, [$this]);
            $this->make($bootstrapper)->bootstrap($this);
            $this['events']->dispatch('bootstrapped: '.$bootstrapper, [$this]);
        }
    }

    /**
     * Register every base service provider.
     */
    protected function registerBaseServiceProviders(): void
    {
        $this->register(new \BareMetal\Events\EventServiceProvider($this));
    }

    public function getLocale(): string
    {
        // TODO: Implement getLocale() method.
    }

    /**
     * Get the application namespace.
     * @throws RuntimeException
     */
    public function getNamespace(): string
    {
        if (! is_null($this->namespace)) {
            return $this->namespace;
        }

        $composer = json_decode(file_get_contents($this->basePath('composer.json')), true);

        foreach ((array) data_get($composer, 'autoload.psr-4') as $namespace => $path) {
            foreach ((array) $path as $pathChoice) {
                if (realpath($this->path()) === realpath($this->basePath($pathChoice))) {
                    return $this->namespace = $namespace;
                }
            }
        }

        throw new RuntimeException('Unable to detect application namespace.');
    }

    public function getProviders(string|ServiceProvider $provider): array
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return Arr::where($this->serviceProviders, fn ($value) => $value instanceof $name);
    }

    /**
     * Determine if the application has been bootstrapped before.
     */
    public function hasBeenBootstrapped(): bool
    {
        return $this->has_been_bootstrapped;
    }

    public function loadDeferredProviders(): void
    {
        foreach ($this->deferred_services as $service => $provider) {
            $this->loadDeferredProvider($service);
        }

        $this->deferred_services = [];
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

    public function setLocale(string $locale): void
    {
        // TODO: Implement setLocale() method.
    }

    public function shouldSkipMiddleware(): bool
    {
        // TODO: Implement shouldSkipMiddleware() method.
    }

    public function terminating(callable|string $callback): MachineContract
    {
        // TODO: Implement terminating() method.
    }

    public function terminate(): void
    {
        // TODO: Implement terminate() method.
    }

    /**
     * Handle the incoming Workshop command.
     * @throws BindingResolutionException
     * @throws CircularDependencyException|ReflectionException
     */
    public function handleCommand(InputInterface $input): int
    {
        $kernel = $this->make(ConsoleKernelInterface::class);

        $status = $kernel->handle(
            $input,
            new ConsoleOutput
        );

        $kernel->terminate($input, $status);

        return $status;
    }

    /**
     * Get the path to the service provider list in the bootstrap directory.
     */
    public function getBootstrapProvidersPath(): string
    {
        return $this->bootstrapPath('providers.php');
    }

    /**
     * Set the bootstrap file directory.
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     */
    public function useBootstrapPath(string $path): static
    {
        $this->bootstrap_path = $path;

        $this->instance('path.bootstrap', $path);

        return $this;
    }

    /**
     * Get the path to the application "app" directory.
     */
    public function path(string $path = ''): string
    {
        return $this->joinPaths($this->app_path ?: $this->basePath('app'), $path);
    }

    /**
     * Get the path to the cached packages.php file.
     */
    public function getCachedPackagesPath(): string
    {
        return $this->normalizeCachePath('APP_PACKAGES_CACHE', 'cache/packages.php');
    }

    /**
     * Register the core class aliases in the container.
     */
    public function registerCoreContainerAliases(): void
    {
        foreach ([
            'app' => [self::class, \BareMetal\Contracts\Chassis\Chassis::class, MachineContract::class, \Psr\Container\ContainerInterface::class],
            'events' => [\BareMetal\Events\Dispatcher::class, \BareMetal\Contracts\Events\Dispatcher::class],
        ] as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($key, $alias);
            }
        }
    }

    /**
     * Get the path to the environment file directory.
     */
    public function environmentPath(): string
    {
        return $this->environment_path ?: $this->base_path;
    }

    /**
     * Set the environment file to be loaded during bootstrapping.
     */
    public function loadEnvironmentFrom(string $file): static
    {
        $this->environment_file = $file;
        return $this;
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
     * Detect the application's current environment.
     */
    public function detectEnvironment(callable $callback): string
    {
        $args = $this->runningInConsole() && isset($_SERVER['argv'])
            ? $_SERVER['argv']
            : null;

        return $this->environment = $this['env'] = (new EnvironmentDetector)->detect($callback, $args);
    }

    /**
     * Set the callback which determines the current container environment.
     */
    public function resolveEnvironmentUsing(?callable $callback): void
    {
        $this->environment_resolver = $callback;
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
     * Register the basic bindings into the container.
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     */
    protected function registerBaseBindings(): void
    {
        static::setInstance($this);

        $this->instance('app', $this);

        $this->instance(Chassis::class, $this);

        $this->singleton(PackageManifest::class, fn () => new PackageManifest(
            new Filesystem, $this->basePath(), $this->getCachedPackagesPath()
        ));
    }


    /**
     * Bind every application path into the container.
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     */
    protected function bindPathsInContainer(): void
    {
        $this->instance('path', $this->path());
        $this->instance('path.base', $this->basePath());
        $this->instance('path.config', $this->configPath());
        //$this->instance('path.database', $this->databasePath());
        $this->instance('path.storage', $this->storagePath());

        $this->useBootstrapPath(value(function () {
            return is_dir($directory = $this->basePath('.scrapyard-io'))
                ? $directory
                : $this->basePath('bootstrap');
        }));
    }

    /**
     * Begin configuring a new ScrapyardIO application instance.
     * @throws ReflectionException
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     */
    public static function setup(?string $base_path = null): MachineBuilder
    {
        $base_path = match (true) {
            is_string($base_path) => $base_path,
            default => static::inferBasePath(),
        };

        return (new MachineBuilder(new static($base_path)))
            ->withKernels()
            ->withEvents(false)
            ->withCommands()
            ->withProviders();
    }

    /**
     * Infer the application's base directory from the environment.
     */
    public static function inferBasePath(): string
    {
        return match (true) {
            isset($_ENV['APP_BASE_PATH']) => $_ENV['APP_BASE_PATH'],
            isset($_SERVER['APP_BASE_PATH']) => $_SERVER['APP_BASE_PATH'],
            default => dirname(array_values(array_filter(
                array_keys(ClassLoader::getRegisteredLoaders()),
                fn ($path) => ! str_starts_with($path, 'phar://'),
            ))[0]),
        };
    }


}
