<?php

namespace Fabricate\Contracts\Cache;

use Closure;
use DateInterval;
use DateTimeInterface;
use Psr\SimpleCache\CacheInterface;
use UnitEnum;

interface CacheRepository extends CacheInterface
{
    /**
     * Retrieve an item from the cache and delete it.
     *
     * @template TCacheValue
     *
     * @param \UnitEnum|array|string $key
     * @param (\Closure(): TCacheValue)|null $default
     * @return (TCacheValue is null ? mixed : TCacheValue)
     */
    public function pull(UnitEnum|array|string $key, ?Closure $default = null);

    /**
     * Store an item in the cache.
     *
     * @param \UnitEnum|string $key
     * @param  mixed  $value
     * @param \DateInterval|\DateTimeInterface|int|null $ttl
     * @return bool
     */
    public function put(UnitEnum|string $key, mixed $value, DateInterval|DateTimeInterface|int|null $ttl = null);

    /**
     * Store an item in the cache if the key does not exist.
     *
     * @param \UnitEnum|string $key
     * @param  mixed  $value
     * @param \DateInterval|\DateTimeInterface|int|null $ttl
     * @return bool
     */
    public function add(UnitEnum|string $key, mixed $value, DateInterval|DateTimeInterface|int|null $ttl = null): bool;

    /**
     * Increment the value of an item in the cache.
     *
     * @param \UnitEnum|string $key
     * @param mixed|int $value
     * @return int|bool
     */
    public function increment(UnitEnum|string $key, mixed $value = 1);

    /**
     * Decrement the value of an item in the cache.
     *
     * @param \UnitEnum|string $key
     * @param mixed|int $value
     * @return int|bool
     */
    public function decrement(UnitEnum|string $key, mixed $value = 1): bool|int;

    /**
     * Store an item in the cache indefinitely.
     *
     * @param \UnitEnum|string $key
     * @param  mixed  $value
     * @return bool
     */
    public function forever(UnitEnum|string $key, mixed $value): bool;

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     *
     * @template TCacheValue
     *
     * @param \UnitEnum|string $key
     * @param \DateInterval|\DateTimeInterface|int|\Closure|null $ttl
     * @param  \Closure(): TCacheValue  $callback
     * @return TCacheValue
     */
    public function remember(UnitEnum|string $key, DateInterval|DateTimeInterface|int|Closure|null $ttl, Closure $callback);

    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
     *
     * @template TCacheValue
     *
     * @param \UnitEnum|string $key
     * @param  \Closure(): TCacheValue  $callback
     * @return TCacheValue
     */
    public function sear(UnitEnum|string $key, Closure $callback);

    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
     *
     * @template TCacheValue
     *
     * @param \UnitEnum|string $key
     * @param  \Closure(): TCacheValue  $callback
     * @return TCacheValue
     */
    public function rememberForever(UnitEnum|string $key, Closure $callback);

    /**
     * Set the expiration of a cached item.
     *
     * @param \UnitEnum|string $key
     * @param \DateInterval|\DateTimeInterface|int $ttl
     * @return bool
     */
    public function touch(UnitEnum|string $key, DateInterval|DateTimeInterface|int $ttl): bool;

    /**
     * Remove an item from the cache.
     *
     * @param \UnitEnum|string $key
     * @return bool
     */
    public function forget(UnitEnum|string $key): bool;

    /**
     * Get the cache store implementation.
     *
     * @return \Fabricate\Contracts\Cache\Store
     */
    public function getStore(): Store;
}
