<?php

namespace Fabricate\Cache;

use BadMethodCallException;
use Fabricate\Contracts\Cache\CanFlushLocks;
use Fabricate\Contracts\Cache\LockProvider;
use Fabricate\Contracts\Cache\Store;

class MemoizedStore implements CanFlushLocks, LockProvider, Store
{
    /**
     * The memoized cache values.
     *
     * @var array<string, mixed>
     */
    protected $cache = [];

    /**
     * Create a new memoized cache instance.
     *
     * @param  string  $name
     * @param  \Fabricate\Cache\Repository  $repository
     */
    public function __construct(
        protected $name,
        protected $repository,
    ) {
        //
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get(string $key): mixed
    {
        $prefixedKey = $this->prefix($key);

        if (array_key_exists($prefixedKey, $this->cache)) {
            return $this->cache[$prefixedKey];
        }

        return $this->cache[$prefixedKey] = $this->repository->get($key);
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @return array
     */
    public function many(array $keys): array
    {
        [$memoized, $retrieved, $missing] = [[], [], []];

        foreach ($keys as $key) {
            $prefixedKey = $this->prefix($key);

            if (array_key_exists($prefixedKey, $this->cache)) {
                $memoized[$key] = $this->cache[$prefixedKey];
            } else {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            $retrieved = tap($this->repository->many($missing), function ($values) {
                foreach ($values as $key => $value) {
                    $this->cache[$this->prefix($key)] = $value;
                }
            });
        }

        $result = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $memoized)) {
                $result[$key] = $memoized[$key];
            } else {
                $result[$key] = $retrieved[$key];
            }
        }

        return $result;
    }

    /**
     * Store an item in the cache for a given number of seconds.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function put(string $key, mixed $value, int $seconds): bool
    {
        unset($this->cache[$this->prefix($key)]);

        return $this->repository->put($key, $value, $seconds);
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
     *
     * @param  int  $seconds
     * @return bool
     */
    public function putMany(array $values, int $seconds): bool
    {
        foreach ($values as $key => $value) {
            unset($this->cache[$this->prefix($key)]);
        }

        return $this->repository->putMany($values, $seconds);
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function increment(string $key, mixed $value = 1): bool|int
    {
        unset($this->cache[$this->prefix($key)]);

        return $this->repository->increment($key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function decrement(string $key, mixed $value = 1): bool|int
    {
        unset($this->cache[$this->prefix($key)]);

        return $this->repository->decrement($key, $value);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function forever(string $key, mixed $value): bool
    {
        unset($this->cache[$this->prefix($key)]);

        return $this->repository->forever($key, $value);
    }

    /**
     * Get a lock instance.
     *
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return \Fabricate\Contracts\Cache\Lock
     *
     * @throws \BadMethodCallException
     */
    public function lock($name, $seconds = 0, $owner = null)
    {
        if (! $this->repository->getStore() instanceof LockProvider) {
            throw new BadMethodCallException('This cache store does not support locks.');
        }

        return $this->repository->getStore()->lock(...func_get_args());
    }

    /**
     * Restore a lock instance using the owner identifier.
     *
     * @param  string  $name
     * @param  string  $owner
     * @return \Fabricate\Contracts\Cache\Lock
     *
     * @throws \BadMethodCallException
     */
    public function restoreLock($name, $owner)
    {
        if (! $this->repository->getStore() instanceof LockProvider) {
            throw new BadMethodCallException('This cache store does not support locks.');
        }

        return $this->repository->getStore()->restoreLock(...func_get_args());
    }

    /**
     * Flush all locks managed by the store.
     *
     * @throws \BadMethodCallException
     */
    public function flushLocks(): bool
    {
        $store = $this->repository->getStore();

        if (! $store instanceof CanFlushLocks) {
            throw new BadMethodCallException('This cache store does not support flushing locks.');
        }

        return $store->flushLocks();
    }

    /**
     * Determine if the lock store is separate from the cache store.
     */
    public function hasSeparateLockStore(): bool
    {
        $store = $this->repository->getStore();

        return $store instanceof CanFlushLocks && $store->hasSeparateLockStore();
    }

    /**
     * Adjust the expiration time of a cached item.
     *
     * @param  string  $key
     * @param  int  $seconds
     * @return bool
     */
    public function touch(string $key, int $seconds): bool
    {
        unset($this->cache[$this->prefix($key)]);

        return $this->repository->touch($key, $seconds);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        unset($this->cache[$this->prefix($key)]);

        return $this->repository->forget($key);
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush(): bool
    {
        $this->cache = [];

        return $this->repository->flush();
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->repository->getPrefix();
    }

    /**
     * Prefix the given key.
     *
     * @param  string  $key
     * @return string
     */
    protected function prefix($key)
    {
        return $this->getPrefix().$key;
    }
}
