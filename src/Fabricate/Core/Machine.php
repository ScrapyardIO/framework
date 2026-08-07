<?php

namespace Fabricate\Core;

use Closure;
use Composer\Autoload\ClassLoader;
use Fabricate\Chassis\Chassis;
use Fabricate\Chassis\Contracts\WireframeServiceContainer;
use Fabricate\Console\WorkshopInstance;
use Fabricate\Contracts\Chassis\ChassisException;
use Fabricate\Contracts\Core\CachesConfiguration;
use Fabricate\Contracts\Core\Program;
use Fabricate\Contracts\Core\ScrapyardIOException;
use Fabricate\Core\Setup\AssemblyLine;
use Fabricate\Events\Dispatcher as EventDispatcher;
use Fabricate\Filesystem\Filesystem;
use Fabricate\Log\LogManager;
use Fabricate\NutsAndBolts\Arr;
use Fabricate\NutsAndBolts\Collection;
use Fabricate\NutsAndBolts\Env;
use Fabricate\NutsAndBolts\ServiceProvider;
use Fabricate\NutsAndBolts\Str;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use function Fabricate\Filesystem\Helpers\join_paths;
use Fabricate\Contracts\Console\CLIKernel as ConsoleKernelInterface;
use Fabricate\Contracts\Sketches\SketchKernel as SketchKernelInterface;

class Machine extends Chassis implements Program, CachesConfiguration
{
    /**
     * The ScrapyardIO framework version.
     *
     * @var string
     */
    const string VERSION = '0.7.0';

    /**
     * The array of registered callbacks.
     *
     * @var callable[]
     */
    protected array $registeredCallbacks = [];

    /**
     * The custom bootstrap path defined by the developer.
     *
     * @var string
     */
    protected string $bootstrapPath = "";

    /**
     * The custom application path defined by the developer.
     *
     * @var string
     */
    protected string $appPath = "";

    /**
     * The custom configuration path defined by the developer.
     *
     * @var string
     */
    protected string $configPath = "";

    /**
     * The custom database path defined by the developer.
     *
     * @var string
     */
    protected string $databasePath = "";

    /**
     * The custom language path defined by the developer.
     *
     * @var string
     */
    protected string $langPath = "";

    /**
     * The custom storage path defined by the developer.
     *
     * @var string
     */
    protected string $storagePath = "";

    /**
     * The custom environment path defined by the developer.
     *
     * @var string
     */
    protected string $environmentPath = "";

    /**
     * The environment file to load during bootstrapping.
     *
     * @var string
     */
    protected string $environmentFile = '.env';

    /**
     * Indicates if the application is running in the console.
     *
     * @var bool|null
     */
    protected ?bool $isRunningInConsole = null;
    protected ?bool $isRunningInProduction = null;

    /**
     * The application namespace.
     *
     * @var ?string
     */
    protected ?string $namespace = null;

    /**
     * The prefixes of absolute cache paths for use during normalization.
     *
     * @var string[]
     */
    protected array $absoluteCachePathPrefixes = ['/', '\\'];

    /**
     * Indicates if the application has been bootstrapped before.
     *
     * @var bool
     */
    protected bool $hasBeenBootstrapped = false;

    /**
     * Indicates if the application has "booted".
     *
     * @var bool
     */
    protected bool $booted = false;

    /**
     * The array of booting callbacks.
     *
     * @var callable[]
     */
    protected array $bootingCallbacks = [];

    /**
     * The array of booted callbacks.
     *
     * @var callable[]
     */
    protected array $bootedCallbacks = [];

    /**
     * The array of terminating callbacks.
     *
     * @var callable[]
     */
    protected array $terminatingCallbacks = [];

    /**
     * Every registered service provider.
     *
     * @var array<string, ServiceProvider>
     */
    protected array $serviceProviders = [];

    /**
     * The names of the loaded service providers.
     *
     * @var array
     */
    protected array $loadedProviders = [];

    /**
     * The deferred services and their providers.
     *
     * @var array
     */
    protected array $deferredServices = [];

    /**
     * Create a new Fabricate application instance.
     *
     * @param string|null $basePath
     * @throws ReflectionException|ChassisException
     */
    public function __construct(
        protected ?string $basePath = null
    ) {
        if ($basePath) {
            $this->setBasePath($basePath);
        }

        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerCoreContainerAliases();
    }

    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version(): string
    {
        return static::VERSION;
    }

    /**
     * @throws ChassisException
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
     * Get the path to the cached packages.php file.
     *
     * @return string
     */
    public function getCachedPackagesPath(): string
    {
        return $this->normalizeCachePath('APP_PACKAGES_CACHE', 'cache/packages.php');
    }

    /**
     * Join the given paths together.
     *
     * @param string $basePath
     * @param string $path
     * @return string
     */
    public function joinPaths(string $basePath, string $path = ''): string
    {
        return join_paths($basePath, $path);
    }

    public function basePath(string $path = ''): string
    {
        return $this->joinPaths($this->basePath, $path);
    }

    public function bootstrapPath(string $path = ''): string
    {
        return $this->joinPaths($this->bootstrapPath, $path);
    }

    /**
     * Get the path to the service provider list in the bootstrap directory.
     */
    public function getBootstrapProvidersPath(): string
    {
        return $this->bootstrapPath('providers.php');
    }

    /**
     * Set the base path for the application.
     *
     * @param string $basePath
     * @return $this
     * @throws ReflectionException|ChassisException
     */
    public function setBasePath(string $basePath): static
    {
        $this->basePath = rtrim($basePath, '\/');

        $this->bindPathsInContainer();

        return $this;
    }

    /**
     * Get the path to the application "app" directory.
     *
     * @param string $path
     * @return string
     */
    public function path(string $path = ''): string
    {
        return $this->joinPaths($this->appPath ?: $this->basePath('app'), $path);
    }

    public function configPath(string $path = ''): string
    {
        return $this->joinPaths($this->configPath ?: $this->basePath('config'), $path);
    }

    public function databasePath(string $path = ''): string
    {
        return $this->joinPaths($this->databasePath ?: $this->basePath('database'), $path);
    }

    public function langPath(string $path = ''): string
    {
        return $this->joinPaths($this->langPath ?: $this->path('lang'), $path);
    }

    public function useLangPath(string $path): static
    {
        $this->langPath = $path;

        $this->instance('path.lang', $path);

        return $this;
    }

    public function storagePath(string $path = ''): string
    {
        if (isset($_ENV['SCRAPYARD_IOSTORAGE_PATH'])) {
            return $this->joinPaths($this->storagePath ?: $_ENV['SCRAPYARD_IOSTORAGE_PATH'], $path);
        }

        if (isset($_SERVER['SCRAPYARD_IOSTORAGE_PATH'])) {
            return $this->joinPaths($this->storagePath ?: $_SERVER['SCRAPYARD_IOSTORAGE_PATH'], $path);
        }

        return $this->joinPaths($this->storagePath ?: $this->basePath('storage'), $path);
    }

    /**
     * Set the bootstrap file directory.
     *
     * @param string $path
     * @return $this
     * @throws ChassisException|ReflectionException
     */
    public function useBootstrapPath(string $path): static
    {
        $this->bootstrapPath = $path;

        $this->instance('path.bootstrap', $path);

        return $this;
    }

    /**
     * Set the storage directory.
     */
    public function useStoragePath(string $path): static
    {
        $this->storagePath = $path;

        $this->instance('path.storage', $path);

        return $this;
    }

    protected function normalizeCachePath($key, $default)
    {
        if (is_null($env = Env::get($key))) {
            return $this->bootstrapPath($default);
        }

        return Str::startsWith($env, $this->absoluteCachePathPrefixes)
            ? $env
            : $this->basePath($env);
    }

    /**
     * Bind every application path into the chassis.
     *
     * @return void
     * @throws ReflectionException|ChassisException
     */
    protected function bindPathsInContainer(): void
    {
        $this->instance('path', $this->path());
        $this->instance('path.base', $this->basePath());
        $this->instance('path.config', $this->configPath());
        $this->instance('path.database', $this->databasePath());
        $this->instance('path.lang', $this->langPath());
        $this->instance('path.storage', $this->storagePath());

        $this->useBootstrapPath(value(function () {
            return is_dir($directory = $this->basePath('.scrapyard-io'))
                ? $directory
                : $this->basePath('bootstrap');
        }));
    }

    /**
     * Register an existing instance as shared in the container.
     *
     * @template TInstance of mixed
     *
     * @param  string  $abstract
     * @param  TInstance  $instance
     * @return TInstance
     * @throws ChassisException|ReflectionException
     */
    public function instance($abstract, $instance): mixed
    {
        $this->removeAbstractAlias($abstract);

        $isBound = $this->bound($abstract);

        unset($this->aliases[$abstract]);

        // We'll check to determine if this type has been bound before, and if it has
        // we will fire the rebound callbacks registered with the container then it
        // can be updated with consuming classes that have gotten resolved here.
        $this->instances[$abstract] = $instance;

        if ($isBound) {
            $this->rebound($abstract);
        }

        return $instance;
    }

    public function cliMachine(): string
    {
        return WorkshopInstance::class;
    }

    /**
     * @throws ReflectionException
     */
    protected function registerBaseBindings(): void
    {
        static::setInstance($this);

        $this->instance('app', $this);

        $this->instance(Chassis::class, $this);

        $this->singleton('events', fn (Machine $app): EventDispatcher => new EventDispatcher($app));
        // Early bind for HandleExceptions (runs before RegisterProviders).
        $this->singleton('log', fn (Machine $app): LogManager => new LogManager($app));
        //$this->singleton('files', fn (): Filesystem => new Filesystem);
        //$this->singleton('filesystem', fn (Machine $app): FilesystemManager => new FilesystemManager($app));
        //$this->singleton('filesystem.disk', fn (Machine $app) => $app['filesystem']->disk());
        //$this->singleton('filesystem.cloud', fn (Machine $app) => $app->bound('config')
        //    ? $app['filesystem']->cloud()
        //    : $app['filesystem']->disk()
        //);
        //$this->singleton('queue', fn (Machine $app): QueueManager => new QueueManager($app));
        //$this->singleton('queue.connection', fn (Machine $app) => $app['queue']->connection());
        //$this->singleton('queue.failer', fn (): NullFailedJobProvider => new NullFailedJobProvider());
        //$this->singleton('bus', fn (Machine $app): BusDispatcher => new BusDispatcher(
        //    $app,
        //    fn ($connection = null) => $app['queue']->connection($connection)
        //));

        $this->singleton(PackageManifest::class, fn () => new PackageManifest(
            new Filesystem, $this->basePath(), $this->getCachedPackagesPath()
        ));
    }

    protected function registerBaseServiceProviders(): void
    {
        $providers = [
            'app' => [self::class, WireframeServiceContainer::class, Program::class, ContainerInterface::class],
            'events' => [\Fabricate\Events\Dispatcher::class, \Fabricate\Contracts\Events\Dispatcher::class],
            'log' => [LogManager::class, LoggerInterface::class],
            'files' => [\Fabricate\Filesystem\Filesystem::class],
            'filesystem' => [\Fabricate\Filesystem\FilesystemManager::class, \Fabricate\Contracts\Filesystem\FilesystemFactory::class],
            'filesystem.disk' => [\Fabricate\Contracts\Filesystem\Filesystem::class],
            'filesystem.cloud' => [\Fabricate\Contracts\Filesystem\Cloud::class],
            'config' => [\Fabricate\Config\Repository::class, \Fabricate\Contracts\Config\Repository::class],
            'cache' => [\Fabricate\Cache\CacheManager::class, \Fabricate\Contracts\Cache\CacheFactory::class, \Fabricate\Contracts\Cache\Factory::class],
            'cache.store' => [\Fabricate\Cache\CacheRepository::class, \Fabricate\Contracts\Cache\Repository::class, \Psr\SimpleCache\CacheInterface::class],
            'db' => [\Fabricate\Database\DatabaseManager::class, \Fabricate\Database\ConnectionResolverInterface::class],
            'db.connection' => [\Fabricate\Database\Connection::class],
            'db.schema' => [\Fabricate\Database\Schema\Builder::class],
            'encrypter' => [
                \Fabricate\Encryption\Encrypter::class,
                \Fabricate\Contracts\Encryption\Encrypter::class,
                \Fabricate\Contracts\Encryption\StringEncrypter::class,
            ],
            'hash' => [
                \Fabricate\Hashing\HashManager::class,
                \Fabricate\Contracts\Hashing\Hasher::class,
            ],
            'http' => [\Fabricate\Http\Client\Factory::class],
            'redis' => [\Fabricate\Redis\RedisManager::class, \Fabricate\Contracts\Redis\Factory::class],
            'redis.connection' => [\Fabricate\Redis\Connections\Connection::class, \Fabricate\Contracts\Redis\Connection::class],
            'process' => [\Fabricate\Process\Factory::class],
            'pipeline' => [\Fabricate\Pipeline\Pipeline::class, \Fabricate\Contracts\Pipeline\Pipeline::class],
            'bus' => [
                \Fabricate\Bus\Dispatcher::class,
                \Fabricate\Contracts\Bus\Dispatcher::class,
                \Fabricate\Contracts\Bus\QueueingDispatcher::class,
            ],
            'queue' => [
                \Fabricate\Queue\QueueManager::class,
                \Fabricate\Contracts\Queue\Factory::class,
                \Fabricate\Contracts\Queue\QueueFactory::class,
            ],
            'queue.connection' => [\Fabricate\Contracts\Queue\Queue::class],
        ];

        foreach ($providers as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($key, $alias);
            }
        }
    }

    protected function registerCoreContainerAliases(): void
    {
        // EventServiceProvider is registered via AssemblyLine::withEvents() on booting — do not double-register here.
        // LogServiceProvider is in DefaultProviders (singletonIf; Machine already binds `log` early).
        //$this->register(new ContextServiceProvider($this));
    }

    public function hasDebugModeEnabled(): bool
    {
        return (bool) $this['config']->get('app.debug');
    }

    public function environment(...$environments): bool|string
    {
        if ($environments !== []) {
            $patterns = is_array($environments[0]) ? $environments[0] : $environments;

            return Str::is($patterns, $this['env']);
        }

        return $this['env'];
    }

    public function runningInProduction(): bool
    {
        return env('APP_ENV', 'local') == 'production';
    }

    public function runningInConsole(): bool
    {
        if (is_null($this->isRunningInConsole)) {
            $this->isRunningInConsole = Env::get('APP_RUNNING_IN_CONSOLE') ?? (\PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg');
        }

        return $this->isRunningInConsole;
    }

    public function runningUnitTests(): bool
    {
        return $this->bound('env') && $this['env'] === 'testing';
    }

    /**
     * Determine if the application is currently down for maintenance.
     */
    public function isDownForMaintenance(): bool
    {
        return false;
    }

    /**
     * @throws ReflectionException
     */
    public function registerConfiguredProviders(): void
    {
        $providers = new Collection($this->make('config')->get('machine.providers'))
            ->partition(fn ($provider) => str_starts_with($provider, 'Fabricate\\'));

        $providers->splice(1, 0, [$this->make(PackageManifest::class)->providers()]);

        new ProviderRepository($this, new Filesystem, $this->getCachedServicesPath())
            ->load($providers->collapse()->toArray());

        $this->fireAppCallbacks($this->registeredCallbacks);
    }

    /**
     * Add an array of services to the application's deferred services.
     *
     * @param  array<string, class-string>  $services
     * @return void
     */
    public function addDeferredServices(array $services): void
    {
        $this->deferredServices = array_merge($this->deferredServices, $services);
    }

    /**
     * Get the registered service provider instance if it exists.
     *
     * @param string|ServiceProvider $provider
     * @return ServiceProvider|null
     */
    public function getProvider(string|ServiceProvider $provider): ServiceProvider|null
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return $this->serviceProviders[$name] ?? null;
    }


    /**
     * @throws ReflectionException
     */
    public function register(string|ServiceProvider $provider, bool $force = false): ServiceProvider
    {
        if (($registered = $this->getProvider($provider)) && ! $force) {
            return $registered;
        }

        // If the given "provider" is a string, we will resolve it, passing in the
        // application instance automatically for the developer. This is simply
        // a more convenient way of specifying your service provider classes.
        if (is_string($provider)) {
            $provider = $this->resolveProvider($provider);
        }

        $provider->register();

        // If there are bindings / singletons set as properties on the provider we
        // will spin through them and register them with the application, which
        // serves as a convenience layer while registering a lot of bindings.
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

        // If the application has already booted, we will call this boot method on
        // the provider class so it has an opportunity to do its boot logic and
        // will be ready for any usage by this developer's application logic.
        if ($this->isBooted()) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    /**
     * @throws ReflectionException
     */
    public function registerDeferredProvider(string $provider, ?string $service = null): void
    {
        // Once the provider that provides the deferred service has been registered we
        // will remove it from our local list of the deferred services with related
        // providers so that this container does not try to resolve it out again.
        if ($service) {
            unset($this->deferredServices[$service]);
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
     * @throws ReflectionException
     */
    public function boot(): void
    {
        if ($this->isBooted()) {
            return;
        }

        // Once the application has booted we will also fire some "booted" callbacks
        // for any listeners that need to do work after this initial booting gets
        // finished. This is useful when ordering the boot-up processes we run.
        $this->fireAppCallbacks($this->bootingCallbacks);

        array_walk(  $this->serviceProviders, function ($p) {
            $this->bootProvider($p);
        });

        $this->booted = true;

        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    public function booting(callable $callback): void
    {
        $this->bootingCallbacks[] = $callback;
    }

    public function booted(callable $callback): void
    {
        $this->bootedCallbacks[] = $callback;

        if ($this->isBooted()) {
            $callback($this);
        }
    }

    /**
     * Determine if the application has booted.
     *
     * @return bool
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * @throws ReflectionException
     */
    public function bootstrapWith(array $bootstrappers): void
    {
        $this->hasBeenBootstrapped = true;

        foreach ($bootstrappers as $bootstrapper) {
            if (isset($this['events'])) {
                $this['events']->dispatch('bootstrapping: '.$bootstrapper, [$this]);
            }

            $this->make($bootstrapper)->bootstrap($this);

            if (isset($this['events'])) {
                $this['events']->dispatch('bootstrapped: '.$bootstrapper, [$this]);
            }
        }
    }

    public function getLocale(): string
    {
        return $this['config']->get('machine.locale', 'en');
    }

    public function getFallbackLocale(): string
    {
        return $this['config']->get('machine.fallback_locale', 'en');
    }

    public function getNamespace(): string
    {
        if (! is_null($this->namespace)) {
            return $this->namespace;
        }

        $composer = json_decode(file_get_contents($this->basePath('composer.json')), true);

        foreach ((array) data_get($composer, 'autoload.psr-4') as $namespace => $path) {
            if (array_any((array)$path, fn($pathChoice) => realpath($this->path()) === realpath($this->basePath($pathChoice)))) {
                return $this->namespace = $namespace;
            }
        }

        throw new ScrapyardIOException('Unable to detect application namespace.');
    }

    public function getProviders(string|ServiceProvider $provider): array
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return Arr::where($this->serviceProviders, fn ($value) => $value instanceof $name);
    }

    public function hasBeenBootstrapped(): bool
    {
        return $this->hasBeenBootstrapped;
    }

    public function loadDeferredProviders(): void
    {
        // We will simply spin through each of the deferred providers and register each
        // one and boot them if the application has booted. This should make each of
        // the remaining services available to this application for immediate use.
        foreach ($this->deferredServices as $service => $provider) {
            $this->loadDeferredProvider($service);
        }

        $this->deferredServices = [];
    }

    /**
     * Determine if the given abstract type has been bound.
     */
    public function bound(string $abstract): bool
    {
        return $this->isDeferredService($abstract) || parent::bound($abstract);
    }

    /**
     * Resolve the given type from the container, loading deferred providers first.
     *
     * @template TClass of object
     *
     * @param  string|class-string<TClass>  $abstract
     * @return ($abstract is class-string<TClass> ? TClass : mixed)
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        $this->loadDeferredProviderIfNeeded($abstract = $this->getAlias($abstract));

        return parent::make($abstract, $parameters);
    }

    /**
     * Resolve the given type from the container, loading deferred providers first.
     *
     * @template TClass of object
     *
     * @param  callable|string|class-string<TClass>  $abstract
     * @return ($abstract is class-string<TClass> ? TClass : mixed)
     */
    protected function resolve(callable|string $abstract, array $parameters = [], bool $raiseEvents = true): mixed
    {
        $this->loadDeferredProviderIfNeeded($abstract = $this->getAlias($abstract));

        return parent::resolve($abstract, $parameters, $raiseEvents);
    }

    /**
     * Load the deferred provider if the given type is a deferred service and the instance has not been loaded.
     */
    protected function loadDeferredProviderIfNeeded(string $abstract): void
    {
        if ($this->isDeferredService($abstract) && ! isset($this->instances[$abstract])) {
            $this->loadDeferredProvider($abstract);
        }
    }

    /**
     * Load the provider for a deferred service.
     *
     * @param string $service
     * @return void
     */
    public function loadDeferredProvider(string $service): void
    {
        if (! $this->isDeferredService($service)) {
            return;
        }

        $provider = $this->deferredServices[$service];

        // If the service provider has not already been loaded and registered we can
        // register it with the application and remove the service from this list
        // of deferred services, since it will already be loaded on subsequent.
        if (! isset($this->loadedProviders[$provider])) {
            $this->registerDeferredProvider($provider, $service);
        }
    }

    /**
     * Determine if the given service is a deferred service.
     *
     * @param string $service
     * @return bool
     */
    public function isDeferredService(string $service): bool
    {
        return isset($this->deferredServices[$service]);
    }


    public function setLocale(string $locale): void
    {
        $previous = $this['config']->get('machine.locale');

        $this['config']->set('machine.locale', $locale);
    }

    public function terminating(callable|string $callback): WireframeServiceContainer
    {
        $this->terminatingCallbacks[] = $callback;

        return $this;
    }

    /**
     * @throws ReflectionException
     */
    public function terminate(): void
    {
        $index = 0;

        while ($index < count($this->terminatingCallbacks)) {
            $this->call($this->terminatingCallbacks[$index]);

            $index++;
        }
    }

    public function environmentFile(): string
    {
        return $this->environmentFile ?: '.env';
    }

    public function loadEnvironmentFrom(string $file): static
    {
        $this->environmentFile = $file;

        return $this;
    }

    public function environmentPath(): string
    {
        return $this->environmentPath ?: $this->basePath;
    }

    public function environmentFilePath(): string
    {
        return $this->environmentPath().DIRECTORY_SEPARATOR.$this->environmentFile();
    }

    public function detectEnvironment(Closure $callback): string
    {
        $args = (!$this->runningInProduction()) && isset($_SERVER['argv'])
            ? $_SERVER['argv']
            : null;

        return $this['env'] = (new EnvironmentDetector)->detect($callback, $args);
    }

    /**
     * Handle the incoming Artisan command.
     *
     * @param InputInterface $input
     * @return int
     * @throws ChassisException
     * @throws ReflectionException
     */
    public function handleCommand(InputInterface $input): int
    {
        $kernel = $this->make(ConsoleKernelInterface::class);

        $status = $kernel->handle(
            $input,
            new ConsoleOutput()
        );

        $kernel->terminate($input, $status);

        return $status;
    }

    /**
     * Handle the incoming Runner (sketch) console input.
     *
     * @throws ChassisException
     * @throws ReflectionException
     */
    public function handleSketch(InputInterface $input): int
    {
        $kernel = $this->make(SketchKernelInterface::class);

        $status = $kernel->handle(
            $input,
            new ConsoleOutput()
        );

        $kernel->terminate($input, $status);

        return $status;
    }

    /**
     * Determine if the application events are cached.
     *
     * @return bool
     * @throws ReflectionException|ChassisException
     */
    public function eventsAreCached(): bool
    {
        if ($this->bound('events.cached')) {
            return (bool) $this->make('events.cached');
        }

        return $this->instance(
            'events.cached', is_file($this->getCachedEventsPath())
        );
    }

    /**
     * Get the path to the events cache file.
     *
     * @return string
     */
    public function getCachedEventsPath(): string
    {
        return $this->normalizeCachePath('APP_EVENTS_CACHE', 'cache/events.php');
    }

    /**
     * Call the booting callbacks for the application.
     *
     * @param  callable[]  $callbacks
     * @return void
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
     * Boot the given service provider.
     *
     * @param ServiceProvider $provider
     * @return void
     * @throws ChassisException|ReflectionException
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
     *
     * @param ServiceProvider $provider
     * @return void
     */
    protected function markAsRegistered(ServiceProvider $provider): void
    {
        $class = get_class($provider);

        $this->serviceProviders[$class] = $provider;

        $this->loadedProviders[$class] = true;
    }

    /**
     * Infer the application's base directory from the environment.
     *
     * @return string
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

    /**
     * @throws ReflectionException
     */
    public static function configure(?string $basePath = null): AssemblyLine
    {
        $basePath = match (true) {
            is_string($basePath) => $basePath,
            default => static::inferBasePath(),
        };

        return new AssemblyLine(new static($basePath))
            ->withKernels()
            ->withEvents()
            ->withCommands()
            ->withProviders()
            ->withExceptions();
    }


}