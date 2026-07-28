<?php

namespace DeptOfScrapyardRobotics\Tests\Queue;

use Exception;
use Fabricate\Chassis\Chassis;
use Fabricate\Contracts\Events\Dispatcher;
use Fabricate\Contracts\Queue\QueueableEntity;
use Fabricate\Contracts\Queue\ShouldBeUnique;
use Fabricate\Contracts\Queue\ShouldQueue;
use Fabricate\Contracts\Queue\ShouldQueueAfterCommit;
use Fabricate\Database\DatabaseTransactionsManager;
use Fabricate\Queue\InteractsWithQueue;
use Fabricate\Queue\Jobs\SyncJob;
use Fabricate\Queue\SyncQueue;
use LogicException;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class QueueSyncQueueTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function tearDown(): void
    {
        Chassis::setInstance(null);

        parent::tearDown();
    }

    public function testPushShouldFireJobInstantly()
    {
        unset($_SERVER['__sync.test']);

        $sync = new SyncQueue;
        $container = new Chassis;
        $sync->setContainer($container);

        $sync->push(SyncQueueTestHandler::class, ['foo' => 'bar']);
        $this->assertInstanceOf(SyncJob::class, $_SERVER['__sync.test'][0]);
        $this->assertEquals(['foo' => 'bar'], $_SERVER['__sync.test'][1]);
    }

    public function testFailedJobGetsHandledWhenAnExceptionIsThrown()
    {
        unset($_SERVER['__sync.failed']);

        $sync = new SyncQueue;
        $container = new Chassis;
        Chassis::setInstance($container);
        $events = m::mock(Dispatcher::class);
        $events->shouldReceive('dispatch')->times(4);
        $container->instance('events', $events);
        $container->instance(Dispatcher::class, $events);
        $sync->setContainer($container);

        try {
            $sync->push(FailingSyncQueueTestHandler::class, ['foo' => 'bar']);
        } catch (Exception) {
            $this->assertTrue($_SERVER['__sync.failed']);
        }

        Chassis::setInstance();
    }

    public function testFailedJobHasAccessToJobInstance()
    {
        unset($_SERVER['__sync.failed']);

        $sync = new SyncQueue;
        $container = new Chassis;
        $container->bind(\Fabricate\Contracts\Events\Dispatcher::class, \Fabricate\Events\Dispatcher::class);
        $container->bind(\Fabricate\Contracts\Bus\Dispatcher::class, \Fabricate\Bus\Dispatcher::class);
        $container->bind(\Fabricate\Contracts\Chassis\WireframeServiceContainer::class, \Fabricate\Chassis\Chassis::class);
        $sync->setContainer($container);

        SyncQueue::createPayloadUsing(function ($connection, $queue, $payload) {
            return ['data' => ['extra' => 'extraValue']];
        });

        try {
            $sync->push(new FailingSyncQueueJob());
        } catch (LogicException) {
            $this->assertSame('extraValue', $_SERVER['__sync.failed']);
        }
    }

    public function testCreatesPayloadObject()
    {
        $sync = new SyncQueue;
        $container = new Chassis;
        $container->bind(\Fabricate\Contracts\Events\Dispatcher::class, \Fabricate\Events\Dispatcher::class);
        $container->bind(\Fabricate\Contracts\Bus\Dispatcher::class, \Fabricate\Bus\Dispatcher::class);
        $container->bind(\Fabricate\Contracts\Chassis\WireframeServiceContainer::class, \Fabricate\Chassis\Chassis::class);
        $sync->setContainer($container);

        SyncQueue::createPayloadUsing(function ($connection, $queue, $payload) {
            return ['data' => ['extra' => 'extraValue']];
        });

        try {
            $sync->push(new SyncQueueJob());
        } catch (LogicException $e) {
            $this->assertSame('extraValue', $e->getMessage());
        }
    }

    public function testItAddsATransactionCallbackForAfterCommitJobs()
    {
        $sync = new SyncQueue;
        $container = new Chassis;
        $container->bind(\Fabricate\Contracts\Chassis\WireframeServiceContainer::class, \Fabricate\Chassis\Chassis::class);
        $transactionManager = m::mock(DatabaseTransactionsManager::class);
        $transactionManager->shouldReceive('addCallback')->once()->andReturn(null);
        $transactionManager->shouldNotReceive('addCallbackForRollback');
        $container->instance('db.transactions', $transactionManager);

        $sync->setContainer($container);
        $sync->push(new SyncQueueAfterCommitJob());
    }

    public function testItAddsATransactionCallbackForInterfaceBasedAfterCommitJobs()
    {
        $sync = new SyncQueue;
        $container = new Chassis;
        $container->bind(\Fabricate\Contracts\Chassis\WireframeServiceContainer::class, \Fabricate\Chassis\Chassis::class);
        $transactionManager = m::mock(DatabaseTransactionsManager::class);
        $transactionManager->shouldReceive('addCallback')->once()->andReturn(null);
        $transactionManager->shouldNotReceive('addCallbackForRollback');
        $container->instance('db.transactions', $transactionManager);

        $sync->setContainer($container);
        $sync->push(new SyncQueueAfterCommitInterfaceJob());
    }

    public function testItAddsATransactionCallbackForAfterCommitUniqueJobs()
    {
        $sync = new SyncQueue;
        $container = new Chassis;
        $container->bind(\Fabricate\Contracts\Chassis\WireframeServiceContainer::class, \Fabricate\Chassis\Chassis::class);
        $transactionManager = m::mock(DatabaseTransactionsManager::class);
        $transactionManager->shouldReceive('addCallback')->once()->andReturn(null);
        $transactionManager->shouldReceive('addCallbackForRollback')->once()->andReturn(null);
        $container->instance('db.transactions', $transactionManager);

        $sync->setContainer($container);
        $sync->push(new SyncQueueAfterCommitUniqueJob());
    }

    public function testItAddsATransactionCallbackForInterfaceBasedAfterCommitUniqueJobs()
    {
        $sync = new SyncQueue;
        $container = new Chassis;
        $container->bind(\Fabricate\Contracts\Chassis\WireframeServiceContainer::class, \Fabricate\Chassis\Chassis::class);
        $transactionManager = m::mock(DatabaseTransactionsManager::class);
        $transactionManager->shouldReceive('addCallback')->once()->andReturn(null);
        $transactionManager->shouldReceive('addCallbackForRollback')->once()->andReturn(null);
        $container->instance('db.transactions', $transactionManager);

        $sync->setContainer($container);
        $sync->push(new SyncQueueAfterCommitInterfaceUniqueJob());
    }
}

class SyncQueueTestEntity implements QueueableEntity
{
    public function getQueueableId()
    {
        return 1;
    }

    public function getQueueableConnection()
    {
        //
    }

    public function getQueueableRelations()
    {
        //
    }
}

class SyncQueueTestHandler
{
    public function fire($job, $data)
    {
        $_SERVER['__sync.test'] = func_get_args();
    }
}

class FailingSyncQueueTestHandler
{
    public function fire($job, $data)
    {
        throw new Exception;
    }

    public function failed()
    {
        $_SERVER['__sync.failed'] = true;
    }
}

class FailingSyncQueueJob implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle()
    {
        throw new LogicException();
    }

    public function failed()
    {
        $payload = $this->job->payload();

        $_SERVER['__sync.failed'] = $payload['data']['extra'];
    }
}

class SyncQueueJob implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle()
    {
        throw new LogicException($this->getValueFromJob('extra'));
    }

    public function getValueFromJob($key)
    {
        $payload = $this->job->payload();

        return $payload['data'][$key] ?? null;
    }
}

class SyncQueueAfterCommitJob
{
    use InteractsWithQueue;

    public $afterCommit = true;

    public function handle()
    {
    }
}

class SyncQueueAfterCommitInterfaceJob implements ShouldQueueAfterCommit
{
    use InteractsWithQueue;

    public function handle()
    {
    }
}

class SyncQueueAfterCommitUniqueJob implements ShouldBeUnique
{
    use InteractsWithQueue;

    public $afterCommit = true;

    public function handle()
    {
    }
}

class SyncQueueAfterCommitInterfaceUniqueJob implements ShouldBeUnique, ShouldQueueAfterCommit
{
    use InteractsWithQueue;

    public function handle()
    {
    }
}
