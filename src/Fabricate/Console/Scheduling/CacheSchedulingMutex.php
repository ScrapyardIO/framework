<?php

namespace Fabricate\Console\Scheduling;

use DateTimeInterface;
use Fabricate\Cache\DynamoDbStore;
use Fabricate\Contracts\Cache\Factory as Cache;
use Fabricate\Contracts\Cache\LockProvider;

class CacheSchedulingMutex implements SchedulingMutex, CacheAware
{
    /**
     * The cache factory implementation.
     *
     * @var \Fabricate\Contracts\Cache\Factory
     */
    public $cache;

    /**
     * The cache store that should be used.
     *
     * @var string|null
     */
    public $store;

    /**
     * Create a new scheduling strategy.
     *
     * @param  \Fabricate\Contracts\Cache\Factory  $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Attempt to obtain a scheduling mutex for the given event.
     *
     * @param  \Fabricate\Console\Scheduling\Event  $event
     * @param  \DateTimeInterface  $time
     * @return bool
     */
    public function create(Event $event, DateTimeInterface $time)
    {
        $mutexName = $event->mutexName().$time->format('Hi');

        if ($this->shouldUseLocks($this->cache->store($this->store)->getStore())) {
            return $this->cache->store($this->store)->getStore()
                ->lock($mutexName, 3600)
                ->acquire();
        }

        return $this->cache->store($this->store)->add(
            $mutexName, true, 3600
        );
    }

    /**
     * Determine if a scheduling mutex exists for the given event.
     *
     * @param  \Fabricate\Console\Scheduling\Event  $event
     * @param  \DateTimeInterface  $time
     * @return bool
     */
    public function exists(Event $event, DateTimeInterface $time)
    {
        $mutexName = $event->mutexName().$time->format('Hi');

        if ($this->shouldUseLocks($this->cache->store($this->store)->getStore())) {
            return ! $this->cache->store($this->store)->getStore()
                ->lock($mutexName, 3600)
                ->get(fn () => true);
        }

        return $this->cache->store($this->store)->has($mutexName);
    }

    /**
     * Determine if the given store should use locks for cache event mutexes.
     *
     * @param  \Fabricate\Contracts\Cache\Store  $store
     * @return bool
     */
    protected function shouldUseLocks($store)
    {
        return $store instanceof LockProvider && ! $store instanceof DynamoDbStore;
    }

    /**
     * Specify the cache store that should be used.
     *
     * @param  string  $store
     * @return $this
     */
    public function useStore($store)
    {
        $this->store = $store;

        return $this;
    }
}
