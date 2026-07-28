<?php

namespace Fabricate\Contracts\Cache;

interface Store
{
    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed;

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @param  array  $keys
     * @return array
     */
    public function many(array $keys): array;

    /**
     * Store an item in the cache for a given number of seconds.
     *
     * @param string $key
     * @param  mixed  $value
     * @param int $seconds
     * @return bool
     */
    public function put(string $key, mixed $value, int $seconds): bool;

    /**
     * Store multiple items in the cache for a given number of seconds.
     *
     * @param  array  $values
     * @param int $seconds
     * @return bool
     */
    public function putMany(array $values, int $seconds): bool;

    /**
     * Increment the value of an item in the cache.
     *
     * @param string $key
     * @param mixed|int $value
     * @return int|bool
     */
    public function increment(string $key, mixed $value = 1): bool|int;

    /**
     * Decrement the value of an item in the cache.
     *
     * @param string $key
     * @param mixed|int $value
     * @return int|bool
     */
    public function decrement(string $key, mixed $value = 1): bool|int;

    /**
     * Store an item in the cache indefinitely.
     *
     * @param string $key
     * @param  mixed  $value
     * @return bool
     */
    public function forever(string $key, mixed $value): bool;

    /**
     * Set the expiration of a cached item.
     *
     * @param string $key
     * @param int $seconds
     * @return bool
     */
    public function touch(string $key, int $seconds): bool;

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool;

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush(): bool;

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix(): string;
}
