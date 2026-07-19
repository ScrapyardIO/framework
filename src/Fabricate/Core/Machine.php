<?php

namespace Fabricate\Core;

use Closure;
use Fabricate\Bus\Dispatcher as BusDispatcher;
use Fabricate\Contracts\Bus\Dispatcher as BusDispatcherContract;
use Fabricate\Contracts\Bus\QueueingDispatcher as QueueingDispatcherContract;
use Fabricate\Contracts\Chassis\BindingResolutionException;
use Fabricate\Contracts\Gfx\Factory;
use Fabricate\Gfx\RenderManager;
use Fabricate\Log\LogServiceProvider;
use Fabricate\Contracts\Chassis\CircularDependencyException;
use Fabricate\Contracts\Filesystem\FileNotFoundException;
use Fabricate\Core\Support\Providers\EventServiceProvider;
use Fabricate\Events\Dispatcher as EventDispatcher;
use Fabricate\Filesystem\Filesystem;
use Fabricate\Filesystem\FilesystemManager;
use Fabricate\Queue\Failed\NullFailedJobProvider;
use Fabricate\Queue\QueueManager;
use Fabricate\NutsAndBolts\Arr;
use Fabricate\NutsAndBolts\Collection;
use Fabricate\NutsAndBolts\Env;
use Fabricate\NutsAndBolts\Str;
use ReflectionException;
use RuntimeException;
use Fabricate\Chassis\Chassis;
use Composer\Autoload\ClassLoader;
use Fabricate\Core\Setup\AssemblyLine;
use Fabricate\NutsAndBolts\ServiceProvider;
use Fabricate\NutsAndBolts\Concerns\Macroable;
use Fabricate\Contracts\Core\CachesConfiguration;
use Fabricate\Contracts\Core\Machine as MachineContract;
use Symfony\Component\Console\Input\InputInterface;
use Fabricate\Contracts\Console\ConsoleKernel as ConsoleKernelInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use function Fabricate\Filesystem\join_paths;

class Machine extends Chassis implements MachineContract, CachesConfiguration
{
    use Macroable;

    /**
     * The ScrapyardIO framework version.
     *
     * @var string
     */
    const string VERSION = '0.5.1';

    /**
     * The base path for the ScrapyardIO installation.
     *
     * @var string
     */
    protected string $basePath;

    /**
     * The array of registered callbacks.
     *
     * @var callable[]
     */
    protected array $registeredCallbacks = [];

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

    /**
     * The application namespace.
     *
     * @var string
     */
    protected ?string $namespace = null;

    /**
     * Indicates if the framework's base configuration should be merged.
     *
     * @var bool
     */
    protected bool $mergeFrameworkConfiguration = true;

    /**
     * The prefixes of absolute cache paths for use during normalization.
     *
     * @var string[]
     */
    protected array $absoluteCachePathPrefixes = ['/', '\\'];

    /**
     * Create a new Fabricate application instance.
     *
     * @param string|null $basePath
     * @throws ReflectionException
     */
    public function __construct(?string $basePath = null)
    {
        if ($basePath) {
            $this->setBasePath($basePath);
        }

        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerCoreContainerAliases();
        //$this->registerScrapyardIOCloudServices();
    }

    /**
     * Register every base service provider.
     *
     * @return void
     * @throws ReflectionException
     */
    protected function registerBaseServiceProviders(): void
    {
        $this->register(new EventServiceProvider($this));
        $this->register(new LogServiceProvider($this));
        //$this->register(new ContextServiceProvider($this));
    }

    /**
     * Register the basic bindings into the container.
     *
     * @return void
     * @throws ReflectionException
     */
    protected function registerBaseBindings(): void
    {
        static::setInstance($this);

        $this->instance('app', $this);

        $this->instance(Chassis::class, $this);

        $this->singleton('events', fn (Machine $app): EventDispatcher => new EventDispatcher($app));
        $this->singleton('files', fn (): Filesystem => new Filesystem);
        $this->singleton('filesystem', fn (Machine $app): FilesystemManager => new FilesystemManager($app));
        $this->singleton('filesystem.disk', fn (Machine $app) => $app['filesystem']->disk());
        $this->singleton('filesystem.cloud', fn (Machine $app) => $app->bound('config')
            ? $app['filesystem']->cloud()
            : $app['filesystem']->disk()
        );
        $this->singleton('queue', fn (Machine $app): QueueManager => new QueueManager($app));
        $this->singleton('queue.connection', fn (Machine $app) => $app['queue']->connection());
        $this->singleton('queue.failer', fn (): NullFailedJobProvider => new NullFailedJobProvider());
        $this->singleton('bus', fn (Machine $app): BusDispatcher => new BusDispatcher(
            $app,
            fn ($connection = null) => $app['queue']->connection($connection)
        ));

        $this->singleton(PackageManifest::class, fn () => new PackageManifest(
            new Filesystem, $this->basePath(), $this->getCachedPackagesPath()
        ));
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
     * Set the base path for the application.
     *
     * @param string $basePath
     * @return $this
     */
    public function setBasePath(string $basePath): static
    {
        $this->basePath = rtrim($basePath, '\/');

        $this->bindPathsInContainer();

        return $this;
    }

    /**
     * Bind all of the application paths in the container.
     *
     * @return void
     */
    protected function bindPathsInContainer(): void
    {
        $this->instance('path', $this->path());
        $this->instance('path.base', $this->basePath());
        $this->instance('path.config', $this->configPath());
        $this->instance('path.database', $this->databasePath());
        $this->instance('path.storage', $this->storagePath());

        $this->useBootstrapPath(value(function () {
            return is_dir($directory = $this->basePath('.scrapyard-io'))
                ? $directory
                : $this->basePath('bootstrap');
        }));

        /*
        $this->useLangPath(value(function () {
            return is_dir($directory = $this->resourcePath('lang'))
                ? $directory
                : $this->basePath('lang');
        }));*/
    }

    /**
     * Set the bootstrap file directory.
     *
     * @param string $path
     * @return $this
     */
    public function useBootstrapPath(string $path): static
    {
        $this->bootstrapPath = $path;

        $this->instance('path.bootstrap', $path);

        return $this;
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
     * Get the path to the application "app" directory.
     *
     * @param string $path
     * @return string
     */
    public function path(string $path = ''): string
    {
        return $this->joinPaths($this->appPath ?: $this->basePath('app'), $path);
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
    /**
     * Register a new registered listener.
     *
     * @param callable $callback
     * @return void
     */
    public function registered(callable $callback): void
    {
        $this->registeredCallbacks[] = $callback;
    }

    /**
     * Register every configured provider.
     *
     * @return void
     * @throws BindingResolutionException|CircularDependencyException
     * @throws FileNotFoundException
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
     * Register a service provider with the application.
     *
     * @param string|ServiceProvider $provider
     * @param bool $force
     * @return ServiceProvider
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
     * Get the path to the service provider list in the bootstrap directory.
     *
     * @return string
     */
    public function getBootstrapProvidersPath(): string
    {
        return $this->bootstrapPath('providers.php');
    }

    /**
     * Get the application's deferred services.
     *
     * @return array
     */
    public function getDeferredServices(): array
    {
        return $this->deferredServices;
    }

    /**
     * Set the application's deferred services.
     *
     * @param  array  $services
     * @return void
     */
    public function setDeferredServices(array $services): void
    {
        $this->deferredServices = $services;
    }

    /**
     * Add an array of services to the application's deferred services.
     *
     * @param  array  $services
     * @return void
     */
    public function addDeferredServices(array $services): void
    {
        $this->deferredServices = array_merge($this->deferredServices, $services);
    }

    /**
     * Remove an array of services from the application's deferred services.
     *
     * @param  array  $services
     * @return void
     */
    public function removeDeferredServices(array $services): void
    {
        foreach ($services as $service) {
            unset($this->deferredServices[$service]);
        }
    }

    /**
     * Determine if the application events are cached.
     *
     * @return bool
     * @throws BindingResolutionException|CircularDependencyException
     */
    public function eventsAreCached(): bool
    {
        if ($this->bound('events.cached')) {
            return (bool) $this->make('events.cached');
        }

        return $this->instance(
            'events.cached', $this['files']->exists($this->getCachedEventsPath())
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
     * Configure the real-time facade namespace.
     *
     * @param string $namespace
     * @return void
     */
    public function provideFacades(string $namespace): void
    {
        AliasLoader::setMagicAliasNamespace($namespace);
    }

    /**
     * Handle the incoming Artisan command.
     *
     * @param InputInterface $input
     * @return int
     * @throws BindingResolutionException|CircularDependencyException
     * @throws ReflectionException
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
     * Get the environment file the application is using.
     *
     * @return string
     */
    public function environmentFile(): string
    {
        return $this->environmentFile ?: '.env';
    }

    /**
     * Set the environment file to be loaded during bootstrapping.
     *
     * @param string $file
     * @return $this
     */
    public function loadEnvironmentFrom(string $file): static
    {
        $this->environmentFile = $file;

        return $this;
    }

    /**
     * Get the path to the environment file directory.
     *
     * @return string
     */
    public function environmentPath(): string
    {
        return $this->environmentPath ?: $this->basePath;
    }

    /**
     * Detect the application's current environment.
     *
     * @param  Closure  $callback
     * @return string
     */
    public function detectEnvironment(Closure $callback): string
    {
        $args = $this->runningInConsole() && isset($_SERVER['argv'])
            ? $_SERVER['argv']
            : null;

        return $this['env'] = (new EnvironmentDetector)->detect($callback, $args);
    }

    /**
     * Register the core class aliases in the container.
     *
     * @return void
     */
    public function registerCoreContainerAliases(): void
    {
        $providers = [
            'app' => [self::class, \Fabricate\Contracts\Chassis\Chassis::class, \Fabricate\Contracts\Core\Machine::class, \Psr\Container\ContainerInterface::class],
            //'auth' => [\Fabricate\Auth\AuthManager::class, \Fabricate\Contracts\Auth\Factory::class],
            //'auth.driver' => [\Fabricate\Contracts\Auth\Guard::class],
            //'auth.password' => [\Fabricate\Auth\Passwords\PasswordBrokerManager::class, \Fabricate\Contracts\Auth\PasswordBrokerFactory::class],
            //'auth.password.broker' => [\Fabricate\Auth\Passwords\PasswordBroker::class, \Fabricate\Contracts\Auth\PasswordBroker::class],
            //'blade.compiler' => [\Fabricate\View\Compilers\BladeCompiler::class],
            'cache' => [\Fabricate\Cache\CacheManager::class, \Fabricate\Contracts\Cache\Factory::class],
            'cache.store' => [\Fabricate\Cache\Repository::class, \Fabricate\Contracts\Cache\Repository::class, \Psr\SimpleCache\CacheInterface::class],
            //'cache.psr6' => [\Symfony\Component\Cache\Adapter\Psr16Adapter::class, \Symfony\Component\Cache\Adapter\AdapterInterface::class, \Psr\Cache\CacheItemPoolInterface::class],
            'config' => [\Fabricate\Config\Repository::class, \Fabricate\Contracts\Config\Repository::class],
            //'cookie' => [\Fabricate\Cookie\CookieJar::class, \Fabricate\Contracts\Cookie\Factory::class, \Fabricate\Contracts\Cookie\QueueingFactory::class],
            //'db' => [\Fabricate\Database\DatabaseManager::class, \Fabricate\Database\ConnectionResolverInterface::class],
            //'db.connection' => [\Fabricate\Database\Connection::class, \Fabricate\Database\ConnectionInterface::class],
            //'db.schema' => [\Fabricate\Database\Schema\Builder::class],
            'display' => [\Fabricate\Displays\DisplayFactory::class, \Fabricate\Contracts\Displays\DisplayFactory::class],
            //'encrypter' => [\Fabricate\Encryption\Encrypter::class, \Fabricate\Contracts\Encryption\Encrypter::class, \Fabricate\Contracts\Encryption\StringEncrypter::class],
            'events' => [\Fabricate\Events\Dispatcher::class, \Fabricate\Contracts\Events\Dispatcher::class],
            'files' => [\Fabricate\Filesystem\Filesystem::class],
            'filesystem' => [\Fabricate\Filesystem\FilesystemManager::class, \Fabricate\Contracts\Filesystem\Factory::class],
            'filesystem.disk' => [\Fabricate\Contracts\Filesystem\Filesystem::class],
            'filesystem.cloud' => [\Fabricate\Contracts\Filesystem\Cloud::class],
            'framebuffer' => [\Fabricate\Framebuffers\FramebufferManager::class, \Fabricate\Contracts\Framebuffers\Factory::class],
            'gfx' => [\Fabricate\Gfx\RenderManager::class, \Fabricate\Contracts\Gfx\Factory::class],
            'gpio' => [\GeneralPurposeIO\Common\GPIOProtocolManager::class, \GeneralPurposeIO\Contracts\Common\GPIOProtocolFactory::class],
            //'hash' => [\Fabricate\Hashing\HashManager::class],
            //'hash.driver' => [\Fabricate\Contracts\Hashing\Hasher::class],
            'log' => [\Fabricate\Log\LogManager::class, \Psr\Log\LoggerInterface::class],
            //'mail.manager' => [\Fabricate\Mail\MailManager::class, \Fabricate\Contracts\Mail\Factory::class],
            //'mailer' => [\Fabricate\Mail\Mailer::class, \Fabricate\Contracts\Mail\Mailer::class, \Fabricate\Contracts\Mail\MailQueue::class],
            'queue' => [\Fabricate\Queue\QueueManager::class, \Fabricate\Contracts\Queue\Factory::class, \Fabricate\Contracts\Queue\Monitor::class],
            'queue.connection' => [\Fabricate\Contracts\Queue\Queue::class],
            'queue.failer' => [\Fabricate\Queue\Failed\FailedJobProviderInterface::class],
            'bus' => [\Fabricate\Bus\Dispatcher::class, BusDispatcherContract::class, QueueingDispatcherContract::class],
            //'redirect' => [\Fabricate\Routing\Redirector::class],
            'redis' => [\Fabricate\Redis\RedisManager::class, \Fabricate\Contracts\Redis\Factory::class],
            'redis.connection' => [\Fabricate\Redis\Connections\Connection::class, \Fabricate\Contracts\Redis\Connection::class],
            //'request' => [\Fabricate\Http\Request::class, \Symfony\Component\HttpFoundation\Request::class],
            //'router' => [\Fabricate\Routing\Router::class, \Fabricate\Contracts\Routing\Registrar::class, \Fabricate\Contracts\Routing\BindingRegistrar::class],
            //'session' => [\Fabricate\Session\SessionManager::class],
            //'session.store' => [\Fabricate\Session\Store::class, \Fabricate\Contracts\Session\Session::class],
            'actuator' => [\Fabricate\Actuation\ActuatorFactory::class],
            'sensor' => [\Fabricate\Sensors\SensorFactory::class],
            //'translator' => [\Fabricate\Translation\Translator::class, \Fabricate\Contracts\Translation\Translator::class],
            //'url' => [\Fabricate\Routing\UrlGenerator::class, \Fabricate\Contracts\Routing\UrlGenerator::class],
            //'validator' => [\Fabricate\Validation\Factory::class, \Fabricate\Contracts\Validation\Factory::class],
            //'view' => [\Fabricate\View\Factory::class, \Fabricate\Contracts\View\Factory::class],
            'window' => [\Fabricate\Displays\WindowFactoryManager::class, \Fabricate\Contracts\Displays\WindowFactory::class],
        ];
        foreach ($providers as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($key, $alias);
            }
        }
    }

    /**
     * Flush the container of all bindings and resolved instances.
     *
     * @return void
     */
    public function flush(): void
    {
        parent::flush();

        $this->buildStack = [];
        $this->loadedProviders = [];
        $this->bootedCallbacks = [];
        $this->bootingCallbacks = [];
        $this->deferredServices = [];
        $this->reboundCallbacks = [];
        $this->serviceProviders = [];
        $this->resolvingCallbacks = [];
        $this->terminatingCallbacks = [];
        $this->beforeResolvingCallbacks = [];
        $this->afterResolvingCallbacks = [];
        $this->globalBeforeResolvingCallbacks = [];
        $this->globalResolvingCallbacks = [];
        $this->globalAfterResolvingCallbacks = [];
    }

    /**
     * Get the application namespace.
     *
     * @return string
     *
     * @throws \RuntimeException
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
     * Begin configuring a new ScrapyardIO application instance.
     *
     * @param string|null $basePath
     * @return AssemblyLine
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

    protected function normalizeCachePath($key, $default)
    {
        if (is_null($env = Env::get($key))) {
            return $this->bootstrapPath($default);
        }

        return Str::startsWith($env, $this->absoluteCachePathPrefixes)
            ? $env
            : $this->basePath($env);
    }

    public function getCachedServicesPath(): string
    {
        return $this->normalizeCachePath('APP_SERVICES_CACHE', 'cache/services.php');
    }

    public function basePath(string $path = ''): string
    {
        return $this->joinPaths($this->basePath, $path);
    }

    public function bootstrapPath(string $path = ''): string
    {
        return $this->joinPaths($this->bootstrapPath, $path);
    }

    public function configPath(string $path = ''): string
    {
        return $this->joinPaths($this->configPath ?: $this->basePath('config'), $path);
    }

    public function databasePath(string $path = ''): string
    {
        return $this->joinPaths($this->databasePath ?: $this->basePath('database'), $path);
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
     * Set the storage directory.
     *
     * @param  string  $path
     * @return $this
     */
    public function useStoragePath(string $path): static
    {
        $this->storagePath = $path;

        $this->instance('path.storage', $path);

        return $this;
    }

    public function environment(...$environments): bool|string
    {
        if ($environments !== []) {
            $patterns = is_array($environments[0]) ? $environments[0] : $environments;

            return Str::is($patterns, $this['env']);
        }

        return $this['env'];
    }

    public function runningInConsole(): bool
    {
        if ($this->isRunningInConsole === null) {
            $this->isRunningInConsole = Env::get('APP_RUNNING_IN_CONSOLE') ?? (\PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg');
        }

        return $this->isRunningInConsole;
    }

    public function runningUnitTests(): bool
    {
        return $this->bound('env') && $this['env'] === 'testing';
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
     * Boot the given service provider.
     *
     * @param ServiceProvider $provider
     * @return void
     */
    protected function bootProvider(ServiceProvider $provider): void
    {
        $provider->callBootingCallbacks();

        if (method_exists($provider, 'boot')) {
            $this->call([$provider, 'boot']);
        }

        $provider->callBootedCallbacks();
    }


    public function resolveProvider(string $provider): ServiceProvider
    {
        return new $provider($this);
    }

    public function boot(): void
    {
        if ($this->isBooted()) {
            return;
        }

        // Once the application has booted we will also fire some "booted" callbacks
        // for any listeners that need to do work after this initial booting gets
        // finished. This is useful when ordering the boot-up processes we run.
        $this->fireAppCallbacks($this->bootingCallbacks);

        array_walk($this->serviceProviders, function ($p) {
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
     * @throws CircularDependencyException
     * @throws BindingResolutionException
     */
    public function bootstrapWith(array $bootstrappers): void
    {
        $this->hasBeenBootstrapped = true;

        foreach ($bootstrappers as $bootstrapper) {
            if(isset($this['events']))
            {
                $this['events']->dispatch('bootstrapping: '.$bootstrapper, [$this]);
            }

            $this->make($bootstrapper)->bootstrap($this);

            if(isset($this['events']))
            {
                $this['events']->dispatch('bootstrapped: '.$bootstrapper, [$this]);
            }
        }
    }

    public function getLocale(): string
    {
        return $this['config']->get('machine.locale');
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

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @param  array  $parameters
     * @return mixed
     *
     * @throws ReflectionException
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        $this->loadDeferredProviderIfNeeded($abstract = $this->getAlias($abstract));

        return parent::make($abstract, $parameters);
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  callable|string  $abstract
     * @param  array  $parameters
     * @param  bool  $raiseEvents
     * @return mixed
     *
     * @throws ReflectionException
     */
    protected function resolve(callable|string $abstract, array $parameters = [], bool $raiseEvents = true): mixed
    {
        $this->loadDeferredProviderIfNeeded($abstract = $this->getAlias($abstract));

        return parent::resolve($abstract, $parameters, $raiseEvents);
    }

    /**
     * Load the deferred provider if the given type is a deferred service.
     *
     * @param  string  $abstract
     * @return void
     *
     * @throws ReflectionException
     */
    protected function loadDeferredProviderIfNeeded(string $abstract): void
    {
        if ($this->isDeferredService($abstract) && ! isset($this->instances[$abstract])) {
            $this->loadDeferredProvider($abstract);
        }
    }

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param  string  $abstract
     * @return bool
     */
    public function bound(string $abstract): bool
    {
        return $this->isDeferredService($abstract) || parent::bound($abstract);
    }

    /**
     * @throws ReflectionException
     */
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
     * Load the provider for a deferred service.
     *
     * @param string $service
     * @return void
     * @throws ReflectionException
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

        /*$this['translator']->setLocale($locale);

        if(isset($this['events']))
        {
            //$this['events']->dispatch(new LocaleUpdated($locale, $previous));
        }
        */
    }

    /**
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     */
    public function shouldSkipMiddleware(): bool
    {
        return $this->bound('middleware.disable') &&
            $this->make('middleware.disable') === true;
    }

    public function terminating(callable|string $callback): MachineContract
    {
        $this->terminatingCallbacks[] = $callback;

        return $this;
    }

    public function terminate(): void
    {
        $index = 0;

        while ($index < count($this->terminatingCallbacks)) {
            $this->call($this->terminatingCallbacks[$index]);

            $index++;
        }
    }
}
