<?php

namespace DeptOfScrapyardRobotics\Tests\Redis;

use Fabricate\Chassis\Chassis;
use Fabricate\Contracts\Redis\Connector;
use Fabricate\Redis\RedisManager;
use InvalidArgumentException;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class RedisManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testMissingConnectionThrows(): void
    {
        $manager = new RedisManager(new Chassis, 'phpredis', []);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Redis connection [default] not configured.');

        $manager->connection();
    }

    public function testPurgeRemovesCachedConnection(): void
    {
        $connector = m::mock(Connector::class);
        $connection = m::mock(\Fabricate\Redis\Connections\Connection::class);
        $connection->shouldReceive('setName')->twice()->with('default')->andReturnSelf();

        $connector->shouldReceive('connect')->twice()->andReturn($connection);

        $manager = new RedisManager(new Chassis, 'custom', [
            'default' => [
                'host' => '127.0.0.1',
                'port' => 6379,
            ],
        ]);

        $manager->extend('custom', function () use ($connector) {
            return $connector;
        });

        $first = $manager->connection();
        $manager->purge();
        $second = $manager->connection();

        $this->assertSame($connection, $first);
        $this->assertSame($connection, $second);
    }

    public function testUrlConfigurationIsParsedBeforeConnect(): void
    {
        $connector = m::mock(Connector::class);
        $connection = m::mock(\Fabricate\Redis\Connections\Connection::class);
        $connection->shouldReceive('setName')->once()->with('default')->andReturnSelf();

        $connector->shouldReceive('connect')
            ->once()
            ->withArgs(function (array $config) {
                return ($config['host'] ?? null) === 'redis.example'
                    && (string) ($config['port'] ?? '') === '6380'
                    && (string) ($config['database'] ?? '') === '2';
            })
            ->andReturn($connection);

        $manager = new RedisManager(new Chassis, 'custom', [
            'default' => [
                'url' => 'redis://redis.example:6380/2',
            ],
        ]);

        $manager->extend('custom', function () use ($connector) {
            return $connector;
        });

        $this->assertSame($connection, $manager->connection());
    }

    public function testSetDriverSwitchesConnector(): void
    {
        $predisConnector = m::mock(Connector::class);
        $phpRedisConnector = m::mock(Connector::class);
        $connection = m::mock(\Fabricate\Redis\Connections\Connection::class);
        $connection->shouldReceive('setName')->once()->with('default')->andReturnSelf();

        $phpRedisConnector->shouldReceive('connect')->once()->andReturn($connection);
        $predisConnector->shouldReceive('connect')->never();

        $manager = new RedisManager(new Chassis, 'predis', [
            'default' => [
                'host' => '127.0.0.1',
                'port' => 6379,
            ],
        ]);

        $manager->extend('predis', fn () => $predisConnector);
        $manager->extend('phpredis', fn () => $phpRedisConnector);
        $manager->setDriver('phpredis');

        $this->assertSame($connection, $manager->connection());
    }
}
