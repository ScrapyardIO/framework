<?php

namespace Fabricate\Cache;

use Closure;
use Fabricate\Contracts\Cache\Factory as FactoryContract;
use Fabricate\Contracts\Cache\Repository as RepositoryContract;
use Fabricate\Contracts\Cache\Store;
use Fabricate\Contracts\Events\Dispatcher as DispatcherContract;
use Fabricate\NutsAndBolts\Arr;
use Fabricate\NutsAndBolts\RebindsCallbacksToSelf;
use InvalidArgumentException;
use Mockery;
use Mockery\LegacyMockInterface;
use ReflectionException;
use RuntimeException;
use UnitEnum;

use function Fabricate\NutsAndBolts\Helpers\enum_value;

/**
 * @mixin \Fabricate\Cache\CacheRepository
 * @mixin \Fabricate\Contracts\Cache\LockProvider
 */
class CacheManager implements FactoryContract
{
    use RebindsCallbacksToSelf;

    /**
     * The application instance.
     *
     * @var \Fabricate\Contracts\Core\Program
     */
    protected $app;

    /**
     * The array of resolved cache stores.
     *
     * @var array
     */
    protected $stores = [];

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * Create a new Cache manager instance.
     *
     * @param  \Fabricate\Contracts\Core\Program  $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get a cache store instance by name, wrapped in a repository.
     *
     * @param  UnitEnum|string|null  $name
     * @return RepositoryContract
     */
    public function store(UnitEnum|string|null $name = null): RepositoryContract
    {
        $name = enum_value($name) ?? $this->getDefaultDriver();

        return $this->stores[$name] ??= $this->resolve($name);
    }

    /**
     * Get a cache driver instance.
     *
     * @param  UnitEnum|string|null  $driver
     * @return RepositoryContract
     */
    public function driver(UnitEnum|string|null $driver = null): RepositoryContract
    {
        return $this->store($driver);
    }

    /**
     * Get a memoized cache driver instance.
     *
     * @param  UnitEnum|string|null  $driver
     * @return RepositoryContract
     */
    public function memo(UnitEnum|string|null $driver = null): RepositoryContract
    {
        $driver = enum_value($driver) ?? $this->getDefaultDriver();

        $bindingKey = "cache.__memoized:{$driver}";

        $isSpy = isset($this->app['cache']) && $this->app['cache'] instanceof LegacyMockInterface;

        $this->app->scopedIf($bindingKey, function () use ($driver, $isSpy) {
            $repository = $this->repository(
                new MemoizedStore($driver, $this->store($driver)), ['events' => false]
            );

            return $isSpy ? Mockery::spy($repository) : $repository;
        });

        return $this->app->make($bindingKey);
    }

    /**
     * Resolve the given store.
     *
     * @param  string  $name
     * @return \Fabricate\Contracts\Cache\Repository
     *
     * @throws \InvalidArgumentException
     */
    public function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Cache store [{$name}] is not defined.");
        }

        $config = Arr::add($config, 'store', $name);

        return $this->build($config);
    }

    /**
     * Build a cache repository with the given configuration.
     *
     * @param  array  $config
     * @return \Fabricate\Cache\CacheRepository
     *
     * @throws \InvalidArgumentException
     */
    public function build(array $config)
    {
        $config = Arr::add($config, 'store', $config['name'] ?? 'ondemand');

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($config);
        }

        $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($config);
        }

        throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
    }

    /**
     * Call a custom driver creator.
     *
     * @param  array  $config
     * @return mixed
     */
    protected function callCustomCreator(array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $config);
    }

    /**
     * Create an instance of the array cache driver.
     *
     * @param  array  $config
     * @return \Fabricate\Cache\CacheRepository
     */
    protected function createArrayDriver(array $config)
    {
        return $this->repository(new ArrayStore(
            $config['serialize'] ?? false,
            $this->getSerializableClasses($config),
        ), $config);
    }

    /**
     * Create an instance of the failover cache driver.
     *
     * @param  array  $config
     * @return \Fabricate\Cache\CacheRepository
     */
    protected function createFailoverDriver(array $config)
    {
        return $this->repository(new FailoverStore(
            $this,
            $this->app->make(DispatcherContract::class),
            $config['stores']
        ), ['events' => false, ...$config]);
    }

    /**
     * Create an instance of the file cache driver.
     *
     * @param  array  $config
     * @return \Fabricate\Cache\CacheRepository
     */
    protected function createFileDriver(array $config)
    {
        return $this->repository(
            (new FileStore(
                $this->app['files'],
                $config['path'],
                $config['permission'] ?? null,
                $this->getSerializableClasses($config),
            ))
                ->setLockDirectory($config['lock_path'] ?? null),
            $config
        );
    }

    /**
     * Create an instance of the storage cache driver.
     *
     * @param  array  $config
     * @return \Fabricate\Cache\CacheRepository
     */
    protected function createStorageDriver(array $config)
    {
        $disk = $this->app['filesystem']->disk($config['disk'] ?? null);

        if (! $disk instanceof \Fabricate\Contracts\Filesystem\Filesystem) {
            throw new InvalidArgumentException(
                'Cache storage driver requires a Flysystem-backed disk (league/flysystem). Use the file or redis store instead.'
            );
        }

        return $this->repository(new StorageStore(
            $disk,
            $config['path'] ?? '',
            $this->getPrefix($config),
            $this->getSerializableClasses($config),
        ), $config);
    }

    /**
     * Create an instance of the Null cache driver.
     *
     * @return \Fabricate\Cache\CacheRepository
     */
    protected function createNullDriver()
    {
        return $this->repository(new NullStore, []);
    }

    /**
     * Create an instance of the Redis cache driver.
     *
     * @param  array  $config
     * @return \Fabricate\Cache\CacheRepository
     */
    protected function createRedisDriver(array $config)
    {
        $redis = $this->app['redis'];

        $connection = $config['connection'] ?? 'default';

        $store = new RedisStore(
            $redis,
            $this->getPrefix($config),
            $connection,
            $this->getSerializableClasses($config),
        );

        return $this->repository(
            $store->setLockConnection($config['lock_connection'] ?? $connection),
            $config
        );
    }

    /**
     * Create a new cache repository with the given implementation.
     *
     * @param  \Fabricate\Contracts\Cache\Store  $store
     * @param  array  $config
     * @return \Fabricate\Cache\CacheRepository
     */
    public function repository(Store $store, array $config = [])
    {
        return tap(new CacheRepository($store, Arr::only($config, ['store'])), function ($repository) use ($config) {
            if ($config['events'] ?? true) {
                $this->setEventDispatcher($repository);
            }
        });
    }

    /**
     * Set the event dispatcher on the given repository instance.
     *
     * @param  \Fabricate\Cache\CacheRepository  $repository
     * @return void
     */
    protected function setEventDispatcher(CacheRepository $repository)
    {
        if (! $this->app->bound(DispatcherContract::class)) {
            return;
        }

        $repository->setEventDispatcher(
            $this->app[DispatcherContract::class]
        );
    }

    /**
     * Re-set the event dispatcher on all resolved cache repositories.
     *
     * @return void
     */
    public function refreshEventDispatcher()
    {
        array_map($this->setEventDispatcher(...), $this->stores);
    }

    /**
     * Get the cache prefix.
     *
     * @param  array  $config
     * @return string
     */
    protected function getPrefix(array $config)
    {
        return $config['prefix'] ?? $this->app['config']['cache.prefix'];
    }

    /**
     * Get the classes that should be allowed during unserialization.
     *
     * @param  array  $config
     * @return array|bool|null
     */
    protected function getSerializableClasses(array $config)
    {
        return $this->app['config']['cache.serializable_classes'] ?? null;
    }

    /**
     * Get the cache connection configuration.
     *
     * @param  string  $name
     * @return array|null
     */
    protected function getConfig($name)
    {
        return $name !== 'null'
            ? $this->app['config']["cache.stores.{$name}"]
            : ['driver' => 'null'];
    }

    /**
     * Get the default cache driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['cache.default'] ?? 'null';
    }

    /**
     * Set the default cache driver name.
     *
     * @param  \UnitEnum|string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['cache.default'] = enum_value($name);
    }

    /**
     * Unset the given driver instances.
     *
     * @param  array|\UnitEnum|string|null  $name
     * @return $this
     */
    public function forgetDriver($name = null)
    {
        $name ??= $this->getDefaultDriver();

        foreach ((array) $name as $cacheName) {
            $cacheName = enum_value($cacheName);

            if (isset($this->stores[$cacheName])) {
                unset($this->stores[$cacheName]);
            }
        }

        return $this;
    }

    /**
     * Disconnect the given driver and remove from local cache.
     *
     * @param  \UnitEnum|string|null  $name
     * @return void
     */
    public function purge($name = null)
    {
        $name = enum_value($name) ?? $this->getDefaultDriver();

        unset($this->stores[$name]);
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param  string  $driver
     * @param  \Closure  $callback
     *
     * @param-closure-this  $this  $callback
     *
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        try {
            $callback = $this->bindCallbackToSelf($callback) ?? throw new RuntimeException('Unable to bind custom driver callback');
        } catch (ReflectionException $e) {
            throw new RuntimeException('Unable to bind custom driver callback', previous: $e);
        }

        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Set the application instance used by the manager.
     *
     * @param  \Fabricate\Contracts\Core\Program  $app
     * @return $this
     */
    public function setApplication($app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Register a callback to be invoked when an unserializable class is encountered.
     *
     * @param  callable|null  $callback
     * @return void
     */
    public function handleUnserializableClassUsing(?callable $callback): void
    {
        CacheRepository::handleUnserializableClassUsing($callback);
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->store()->$method(...$parameters);
    }
}
