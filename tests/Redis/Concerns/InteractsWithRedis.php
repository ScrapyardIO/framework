<?php

namespace DeptOfScrapyardRobotics\Tests\Redis\Concerns;

use Exception;
use Fabricate\Chassis\Chassis;
use Fabricate\NutsAndBolts\Env;
use Fabricate\Redis\RedisManager;

trait InteractsWithRedis
{
    /**
     * Indicate connection failed if redis is not available.
     *
     * @var bool
     */
    private static $connectionFailedOnceWithDefaultsSkip = false;

    /**
     * Redis manager instances keyed by driver.
     *
     * @var array<string, \Fabricate\Redis\RedisManager>
     */
    private $redis;

    /**
     * Setup redis connection.
     *
     * @return void
     */
    public function setUpRedis()
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('The redis extension is not installed. Please install the extension to enable '.__CLASS__);
        }

        if (static::$connectionFailedOnceWithDefaultsSkip) {
            $this->markTestSkipped('Trying default host/port failed, please set environment variable REDIS_HOST & REDIS_PORT to enable '.__CLASS__);
        }

        $app = $this->app ?? new Chassis;
        $host = Env::get('REDIS_HOST', '127.0.0.1');
        $port = Env::get('REDIS_PORT', 6379);

        foreach (static::redisDriverProvider() as $driver) {
            if (Env::get('REDIS_CLUSTER_HOSTS_AND_PORTS')) {
                $config = [
                    'options' => [
                        'cluster' => 'redis',
                        'prefix' => 'test_',
                    ],
                    'clusters' => [
                        'default' => array_map(
                            static fn ($hostAndPort) => [
                                'host' => explode(':', $hostAndPort)[0],
                                'port' => explode(':', $hostAndPort)[1],
                            ],
                            explode(',', Env::get('REDIS_CLUSTER_HOSTS_AND_PORTS')),
                        ),
                    ],
                ];
            } else {
                $config = [
                    'options' => [
                        'prefix' => 'test_',
                    ],
                    'default' => [
                        'host' => $host,
                        'port' => $port,
                        'database' => 5,
                        'timeout' => 0.5,
                        'name' => 'default',
                    ],
                    'cache' => [
                        'host' => $host,
                        'port' => $port,
                        'database' => 6,
                        'timeout' => 0.5,
                    ],
                ];
            }

            $this->redis[$driver[0]] = new RedisManager($app, $driver[0], $config);
        }

        $defaultDriver = Env::get('REDIS_CLIENT', 'phpredis');

        if (! isset($this->redis[$defaultDriver])) {
            $defaultDriver = array_key_first($this->redis);
        }

        try {
            $this->redis[$defaultDriver]->connection()->flushdb();
        } catch (Exception) {
            if ($host === '127.0.0.1' && (int) $port === 6379 && is_null(Env::get('REDIS_HOST'))) {
                static::$connectionFailedOnceWithDefaultsSkip = true;

                $this->markTestSkipped('Trying default host/port failed, please set environment variable REDIS_HOST & REDIS_PORT to enable '.__CLASS__);
            }
        }

        $app->instance('redis', $this->redis[$defaultDriver]);
    }

    /**
     * Teardown redis connection.
     *
     * @return void
     */
    public function tearDownRedis()
    {
        if (static::$connectionFailedOnceWithDefaultsSkip === true) {
            return;
        }

        if (isset($this->redis['phpredis'])) {
            $this->redis['phpredis']->connection()->flushdb();
        }

        foreach (static::redisDriverProvider() as $driver) {
            if (isset($this->redis[$driver[0]])) {
                $this->redis[$driver[0]]->connection()->disconnect();
            }
        }
    }

    /**
     * Get redis driver provider.
     *
     * @return array
     */
    public static function redisDriverProvider()
    {
        $drivers = [];

        if (class_exists(\Predis\Client::class)) {
            $drivers[] = ['predis'];
        }

        if (extension_loaded('redis')) {
            $drivers[] = ['phpredis'];
        }

        return $drivers;
    }

    /**
     * Run test if redis is available.
     *
     * @param  callable  $callback
     * @return void
     */
    public function ifRedisAvailable($callback)
    {
        $this->setUpRedis();

        $callback();

        $this->tearDownRedis();
    }
}
