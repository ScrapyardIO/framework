<?php

namespace DeptOfScrapyardRobotics\Tests\Redis;

use Fabricate\Chassis\Chassis;
use DeptOfScrapyardRobotics\Tests\Redis\Concerns\InteractsWithRedis;
use Fabricate\Redis\RedisManager;
use PHPUnit\Framework\TestCase;
use Predis\Client as PredisClient;
use Redis;

class RedisConnectorTest extends TestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRedis();
    }

    protected function tearDown(): void
    {
        $this->tearDownRedis();

        parent::tearDown();
    }

    public function testDefaultConfiguration()
    {
        $host = env('REDIS_HOST', '127.0.0.1');
        $port = env('REDIS_PORT', 6379);

        if ($this->hasPredis() && isset($this->redis['predis'])) {
            $predisClient = $this->redis['predis']->connection()->client();
            $parameters = $predisClient->getConnection()->getParameters();
            $this->assertSame('tcp', $parameters->scheme);
            $this->assertEquals($host, $parameters->host);
            $this->assertEquals($port, $parameters->port);
        }

        $phpRedisClient = $this->redis['phpredis']->connection()->client();
        $this->assertEquals($host, $phpRedisClient->getHost());
        $this->assertEquals($port, $phpRedisClient->getPort());
        $this->assertSame('default', $phpRedisClient->client('GETNAME'));
    }

    public function testUrl()
    {
        $host = env('REDIS_HOST', '127.0.0.1');
        $port = env('REDIS_PORT', 6379);

        if ($this->hasPredis()) {
            $predis = new RedisManager(new Chassis, 'predis', [
                'cluster' => false,
                'options' => [
                    'prefix' => 'test_',
                ],
                'default' => [
                    'url' => "redis://{$host}:{$port}",
                    'database' => 5,
                    'timeout' => 0.5,
                ],
            ]);
            $predisClient = $predis->connection()->client();
            $parameters = $predisClient->getConnection()->getParameters();
            $this->assertSame('tcp', $parameters->scheme);
            $this->assertEquals($host, $parameters->host);
            $this->assertEquals($port, $parameters->port);
        }

        $phpRedis = new RedisManager(new Chassis, 'phpredis', [
            'cluster' => false,
            'options' => [
                'prefix' => 'test_',
            ],
            'default' => [
                'url' => "redis://{$host}:{$port}",
                'database' => 5,
                'timeout' => 0.5,
            ],
        ]);
        $phpRedisClient = $phpRedis->connection()->client();
        $this->assertSame("tcp://{$host}", $phpRedisClient->getHost());
        $this->assertEquals($port, $phpRedisClient->getPort());
    }

    public function testUrlWithScheme()
    {
        $host = env('REDIS_HOST', '127.0.0.1');
        $port = env('REDIS_PORT', 6379);

        if ($this->hasPredis()) {
            $predis = new RedisManager(new Chassis, 'predis', [
                'cluster' => false,
                'options' => [
                    'prefix' => 'test_',
                ],
                'default' => [
                    'url' => "tls://{$host}:{$port}",
                    'database' => 5,
                    'timeout' => 0.5,
                ],
            ]);
            $predisClient = $predis->connection()->client();
            $parameters = $predisClient->getConnection()->getParameters();
            $this->assertSame('tls', $parameters->scheme);
            $this->assertEquals($host, $parameters->host);
            $this->assertEquals($port, $parameters->port);
        }

        $phpRedis = new RedisManager(new Chassis, 'phpredis', [
            'cluster' => false,
            'options' => [
                'prefix' => 'test_',
            ],
            'default' => [
                'url' => "tcp://{$host}:{$port}",
                'database' => 5,
                'timeout' => 0.5,
            ],
        ]);
        $phpRedisClient = $phpRedis->connection()->client();
        $this->assertSame("tcp://{$host}", $phpRedisClient->getHost());
        $this->assertEquals($port, $phpRedisClient->getPort());
    }

    public function testScheme()
    {
        $host = env('REDIS_HOST', '127.0.0.1');
        $port = env('REDIS_PORT', 6379);

        if ($this->hasPredis()) {
            $predis = new RedisManager(new Chassis, 'predis', [
                'cluster' => false,
                'options' => [
                    'prefix' => 'test_',
                ],
                'default' => [
                    'scheme' => 'tls',
                    'host' => $host,
                    'port' => $port,
                    'database' => 5,
                    'timeout' => 0.5,
                ],
            ]);
            $predisClient = $predis->connection()->client();
            $parameters = $predisClient->getConnection()->getParameters();
            $this->assertSame('tls', $parameters->scheme);
            $this->assertEquals($host, $parameters->host);
            $this->assertEquals($port, $parameters->port);
        }

        $phpRedis = new RedisManager(new Chassis, 'phpredis', [
            'cluster' => false,
            'options' => [
                'prefix' => 'test_',
            ],
            'default' => [
                'scheme' => 'tcp',
                'host' => $host,
                'port' => $port,
                'database' => 5,
                'timeout' => 0.5,
            ],
        ]);
        $phpRedisClient = $phpRedis->connection()->client();
        $this->assertSame("tcp://{$host}", $phpRedisClient->getHost());
        $this->assertEquals($port, $phpRedisClient->getPort());
    }

    public function testPredisConfigurationWithUsername()
    {
        $this->assertPredisAvailable();
        $host = env('REDIS_HOST', '127.0.0.1');
        $port = env('REDIS_PORT', 6379);
        $username = 'testuser';
        $password = 'testpw';

        $predis = new RedisManager(new Chassis, 'predis', [
            'default' => [
                'host' => $host,
                'port' => $port,
                'username' => $username,
                'password' => $password,
                'database' => 5,
                'timeout' => 0.5,
            ],
        ]);
        $predisClient = $predis->connection()->client();
        $parameters = $predisClient->getConnection()->getParameters();
        $this->assertEquals($username, $parameters->username);
        $this->assertEquals($password, $parameters->password);
    }

    public function testPredisConfigurationWithSentinel()
    {
        $this->assertPredisAvailable();
        $host = env('REDIS_HOST', '127.0.0.1');
        $port = env('REDIS_PORT', 6379);

        $predis = new RedisManager(new Chassis, 'predis', [
            'cluster' => false,
            'options' => [
                'replication' => 'sentinel',
                'service' => 'mymaster',
                'parameters' => [
                    'default' => [
                        'database' => 5,
                    ],
                ],
            ],
            'default' => [
                "tcp://{$host}:{$port}",
            ],
        ]);

        $predisClient = $predis->connection()->client();
        $parameters = $predisClient->getConnection()->getSentinelConnection()->getParameters();
        $this->assertEquals($host, $parameters->host);
    }

    public function testPhpRedisTcpKeepalive()
    {
        $host = env('REDIS_HOST', '127.0.0.1');
        $port = env('REDIS_PORT', 6379);

        $phpRedis = new RedisManager(new Chassis, 'phpredis', [
            'cluster' => false,
            'default' => [
                'host' => $host,
                'port' => $port,
                'database' => 5,
                'timeout' => 0.5,
                'tcp_keepalive' => 60,
            ],
        ]);

        $phpRedisClient = $phpRedis->connection()->client();
        $this->assertEquals(1, $phpRedisClient->getOption(Redis::OPT_TCP_KEEPALIVE));
    }

    public function testPrefixOverrideBehaviour()
    {
        $host = env('REDIS_HOST', '127.0.0.1');
        $port = env('REDIS_PORT', 6379);

        if ($this->hasPredis()) {
            $predis1 = new RedisManager(new Chassis, 'predis', [
                'cluster' => false,
                'options' => [
                    'prefix' => 'test_',
                ],
                'default' => [
                    'scheme' => 'tls',
                    'host' => $host,
                    'port' => $port,
                    'database' => 5,
                    'timeout' => 0.5,
                    'options' => [
                        'prefix' => 'test_default_options_',
                    ],
                ],
            ]);
            $predisClient1 = $predis1->client();
            $this->assertEquals('test_default_options_', $predisClient1->getOptions()->prefix->getPrefix());
    
            $predis2 = new RedisManager(new Chassis, 'predis', [
                'cluster' => false,
                'options' => [
                    'prefix' => 'test_',
                ],
                'default' => [
                    'scheme' => 'tls',
                    'host' => $host,
                    'port' => $port,
                    'database' => 5,
                    'timeout' => 0.5,
                    'options' => [
                        'prefix' => 'test_default_options_',
                    ],
                    'prefix' => 'test_default_config_',
                ],
            ]);
            $predisClient2 = $predis2->client();
            $this->assertEquals('test_default_config_', $predisClient2->getOptions()->prefix->getPrefix());
        }

        $phpRedis1 = new RedisManager(new Chassis, 'phpredis', [
            'cluster' => false,
            'options' => [
                'prefix' => 'test_',
            ],
            'default' => [
                'scheme' => 'tcp',
                'host' => $host,
                'port' => $port,
                'database' => 5,
                'timeout' => 0.5,
                'options' => [
                    'prefix' => 'test_default_options_',
                ],
            ],
        ]);
        $phpRedisClient1 = $phpRedis1->connection()->client();
        $this->assertEquals('test_default_options_', $phpRedisClient1->getOption(Redis::OPT_PREFIX));

        $phpRedis2 = new RedisManager(new Chassis, 'phpredis', [
            'cluster' => false,
            'options' => [
                'prefix' => 'test_',
            ],
            'default' => [
                'scheme' => 'tcp',
                'host' => $host,
                'port' => $port,
                'database' => 5,
                'timeout' => 0.5,
                'options' => [
                    'prefix' => 'test_default_options_',
                ],
                'prefix' => 'test_default_config_',
            ],
        ]);
        $phpRedisClient2 = $phpRedis2->connection()->client();
        $this->assertEquals('test_default_config_', $phpRedisClient2->getOption(Redis::OPT_PREFIX));
    }

    protected function assertPredisAvailable(): void
    {
        if (! class_exists(PredisClient::class)) {
            $this->markTestSkipped('predis/predis is not installed.');
        }
    }

    protected function hasPredis(): bool
    {
        return class_exists(PredisClient::class);
    }

}
