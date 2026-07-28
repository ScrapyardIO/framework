<?php

namespace Fabricate\Cache;

use Fabricate\Contracts\Cache\CanFlushLocks;
use Fabricate\Contracts\Cache\LockProvider;

class NullStore extends TaggableStore implements CanFlushLocks, LockProvider
{
    use RetrievesMultipleKeys;

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get(string $key): mixed
    {
        return null;
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
        return false;
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return false
     */
    public function increment(string $key, mixed $value = 1): bool|int
    {
        return false;
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return false
     */
    public function decrement(string $key, mixed $value = 1): bool|int
    {
        return false;
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
        return false;
    }

    /**
     * Get a lock instance.
     *
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return \Fabricate\Contracts\Cache\Lock
     */
    public function lock($name, $seconds = 0, $owner = null)
    {
        return new NoLock($name, $seconds, $owner);
    }

    /**
     * Restore a lock instance using the owner identifier.
     *
     * @param  string  $name
     * @param  string  $owner
     * @return \Fabricate\Contracts\Cache\Lock
     */
    public function restoreLock($name, $owner)
    {
        return $this->lock($name, 0, $owner);
    }

    /**
     * Flush all locks managed by the store.
     */
    public function flushLocks(): bool
    {
        return true;
    }

    /**
     * Determine if the lock store is separate from the cache store.
     */
    public function hasSeparateLockStore(): bool
    {
        return false;
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
        return false;
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        return true;
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush(): bool
    {
        return true;
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return '';
    }
}
