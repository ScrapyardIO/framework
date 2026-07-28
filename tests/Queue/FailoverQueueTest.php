<?php

namespace DeptOfScrapyardRobotics\Tests\Queue;

use Fabricate\Chassis\Chassis;
use Fabricate\Contracts\Events\Dispatcher;
use Fabricate\Queue\FailoverQueue;
use Fabricate\Queue\QueueManager;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class FailoverQueueTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function tearDown(): void
    {
        Chassis::setInstance(null);

        parent::tearDown();
    }

    public function test_push_fails_over_on_exception()
    {
        $failover = new FailoverQueue($queue = m::mock(QueueManager::class), $events = m::mock(Dispatcher::class), [
            'redis',
            'sync',
        ]);

        $queue->shouldReceive('connection')->once()->with('redis')->andReturn(
            $redis = m::mock('stdClass'),
        );

        $queue->shouldReceive('connection')->once()->with('sync')->andReturn(
            $sync = m::mock('stdClass'),
        );

        $events->shouldReceive('dispatch')->once();

        $redis->shouldReceive('push')->once()->andReturnUsing(
            fn () => throw new \Exception('error')
        );

        $sync->shouldReceive('push')->once();

        $failover->push('some-job');
    }
}
