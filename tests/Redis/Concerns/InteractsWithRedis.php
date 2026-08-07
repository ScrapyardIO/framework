<?php

namespace Tests\Redis\Concerns;

use Exception;
use Fabricate\Chassis\Chassis;
use Fabricate\NutsAndBolts\Env;
use Fabricate\Redis\RedisManager;

trait InteractsWithRedis
{
    private static bool $connectionFailedOnceWithDefaultsSkip = false;

    /**
     * @var array<string, \Fabricate\Redis\RedisManager>|null
     */
    private ?array $redis = null;

    public function setUpRedis(): void
    {
        if (static::$connectionFailedOnceWithDefaultsSkip) {
            $this->markTestSkipped('Trying default host/port failed, please set environment variable REDIS_HOST & REDIS_PORT to enable '.__CLASS__);
        }

        $drivers = static::redisDriverProvider();

        if ($drivers === []) {
            $this->markTestSkipped('No Redis client available. Install ext-redis and/or predis/predis to enable '.__CLASS__);
        }

        $app = $this->app ?? new Chassis;
        $host = Env::get('REDIS_HOST', '127.0.0.1');
        $port = Env::get('REDIS_PORT', 6379);

        foreach ($drivers as $driver) {
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
                            explode(',', (string) Env::get('REDIS_CLUSTER_HOSTS_AND_PORTS')),
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

        $defaultDriver = Env::get('REDIS_CLIENT', extension_loaded('redis') ? 'phpredis' : 'predis');

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

    public function tearDownRedis(): void
    {
        if (static::$connectionFailedOnceWithDefaultsSkip === true || is_null($this->redis)) {
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
     * @return array<int, array{0: string}>
     */
    public static function redisDriverProvider(): array
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

    public function ifRedisAvailable(callable $callback): void
    {
        $this->setUpRedis();

        $callback();

        $this->tearDownRedis();
    }
}
