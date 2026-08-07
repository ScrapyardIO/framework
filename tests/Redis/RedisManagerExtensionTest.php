<?php

namespace Tests\Redis;

use Fabricate\Contracts\Redis\Connector;
use Fabricate\Chassis\Chassis;
use Fabricate\Redis\RedisManager;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class RedisManagerExtensionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Fabricate\Redis\RedisManager
     */
    protected $redis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = new RedisManager(new Chassis, 'my_custom_driver', [
            'default' => [
                'host' => 'some-host',
                'port' => 'some-port',
                'database' => 5,
                'timeout' => 0.5,
            ],
            'clusters' => [
                'my-cluster' => [
                    [
                        'host' => 'some-host',
                        'port' => 'some-port',
                        'database' => 5,
                        'timeout' => 0.5,
                    ],
                ],
            ],
        ]);

        $this->redis->extend('my_custom_driver', function () {
            return new FakeRedisConnector;
        });
    }

    public function testUsingCustomRedisConnectorWithSingleRedisInstance()
    {
        $this->assertSame(
            'my-redis-connection', $this->redis->resolve()
        );
    }

    public function testUsingCustomRedisConnectorWithRedisClusterInstance()
    {
        $this->assertSame(
            'my-redis-cluster-connection', $this->redis->resolve('my-cluster')
        );
    }

    public function testParseConnectionConfigurationForCluster()
    {
        $name = 'my-cluster';
        $config = [
            [
                'url1',
                'url2',
                'url3',
            ],
        ];
        $redis = new RedisManager(new Chassis, 'my_custom_driver', [
            'clusters' => [
                $name => $config,
            ],
        ]);
        $connector = m::mock(Connector::class);
        $connector->shouldReceive('connectToCluster')
            ->once()
            ->withArgs(function ($configArg) use ($config) {
                return $config === $configArg;
            })
            ->andReturn('cluster-connection');

        $redis->extend('my_custom_driver', function () use ($connector) {
            return $connector;
        });

        $this->assertSame('cluster-connection', $redis->resolve($name));
    }
}

class FakeRedisConnector implements Connector
{
    /**
     * Create a new clustered Predis connection.
     *
     * @param  array  $config
     * @param  array  $options
     * @return string
     */
    public function connect(array $config, array $options)
    {
        return 'my-redis-connection';
    }

    /**
     * Create a new clustered Predis connection.
     *
     * @param  array  $config
     * @param  array  $clusterOptions
     * @param  array  $options
     * @return string
     */
    public function connectToCluster(array $config, array $clusterOptions, array $options)
    {
        return 'my-redis-cluster-connection';
    }
}
