<?php

namespace DeptOfScrapyardRobotics\Tests\Redis\Connections;

use Fabricate\Redis\Connections\PhpRedisClusterConnection;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('redis')]
class PhpRedisClusterConnectionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItScansUsingDefaultNode()
    {
        $client = m::mock(\RedisCluster::class);
        $client->shouldReceive('_masters')->once()->andReturn([['127.0.0.1', '6379']]);
        $client->shouldReceive('scan')
            ->once()
            ->with(0, ['127.0.0.1', '6379'], '*', 10)
            ->andReturn(['key']);

        $connection = new PhpRedisClusterConnection($client);
        $this->assertEquals([0, ['key']], $connection->scan(0));
    }

    public function testItOnlyFetchesDefaultNodeOnce()
    {
        $client = m::mock(\RedisCluster::class);
        $client->shouldReceive('_masters')->once()->andReturn([['127.0.0.1', '6379']]);
        $client->shouldReceive('scan')->twice();

        $connection = new PhpRedisClusterConnection($client);
        $connection->scan(0);
        $connection->scan(0);

        $this->addToAssertionCount(1);
    }

    public function testItScansUsingOptionNode()
    {
        $client = m::mock(\RedisCluster::class);
        $client->shouldReceive('scan')
            ->once()
            ->with(0, 'option-node', '*', 10)
            ->andReturn(['key']);

        $connection = new PhpRedisClusterConnection($client);
        $this->assertEquals([0, ['key']], $connection->scan(0, ['node' => 'option-node']));
    }

    public function testItThrowsExceptionWithoutNodes()
    {
        $client = m::mock(\RedisCluster::class);
        $client->shouldReceive('_masters')->once()->andReturn([]);
        $client->shouldReceive('scan');

        $this->expectExceptionMessage('Unable to determine default node. No master nodes found in the cluster.');

        $connection = new PhpRedisClusterConnection($client);
        $connection->scan(0);
    }

    public function testItReturnsFalseWhenCursorIsZeroAndResultIsEmpty()
    {
        $client = m::mock(\RedisCluster::class);
        $client->shouldReceive('_masters')->once()->andReturn([['127.0.0.1', '6379']]);
        $client->shouldReceive('scan')
            ->once()
            ->with(0, ['127.0.0.1', '6379'], '*', 10)
            ->andReturn(false);

        $connection = new PhpRedisClusterConnection($client);
        $this->assertFalse($connection->scan(0));
    }

    public function testItFlushesAllMasterNodes()
    {
        $client = m::mock(\RedisCluster::class);
        $client->shouldReceive('_masters')->once()->andReturn([
            ['127.0.0.1', '6379'],
            ['127.0.0.2', '6379'],
        ]);
        $client->shouldReceive('flushdb')->once()->with(['127.0.0.1', '6379']);
        $client->shouldReceive('flushdb')->once()->with(['127.0.0.2', '6379']);

        $connection = new PhpRedisClusterConnection($client);
        $connection->flushdb();

        $this->addToAssertionCount(1);
    }

    public function testItFlushesAllMasterNodesAsync()
    {
        $client = m::mock(\RedisCluster::class);
        $client->shouldReceive('_masters')->once()->andReturn([
            ['127.0.0.1', '6379'],
            ['127.0.0.2', '6379'],
        ]);
        $client->shouldReceive('rawCommand')->once()->with(['127.0.0.1', '6379'], 'flushdb', 'async');
        $client->shouldReceive('rawCommand')->once()->with(['127.0.0.2', '6379'], 'flushdb', 'async');

        $connection = new PhpRedisClusterConnection($client);
        $connection->flushdb('ASYNC');

        $this->addToAssertionCount(1);
    }
}
