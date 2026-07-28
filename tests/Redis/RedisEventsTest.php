<?php

namespace DeptOfScrapyardRobotics\Tests\Redis;

use Exception;
use Fabricate\Contracts\Events\Dispatcher;
use Fabricate\Redis\Connections\PhpRedisConnection;
use Fabricate\Redis\Events\CommandExecuted;
use Fabricate\Redis\Events\CommandFailed;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Redis;

#[RequiresPhpExtension('redis')]
class RedisEventsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCommandFailedEventIsDispatched()
    {
        $exception = new Exception('Test exception');

        $client = m::mock(Redis::class);
        $client->shouldReceive('get')->with('key')->andThrow($exception);

        $events = m::mock(Dispatcher::class);
        $events->shouldReceive('dispatch')->once()->with(m::on(function ($event) use ($exception) {
            return $event instanceof CommandFailed
                && $event->command === 'get'
                && $event->parameters === ['key']
                && $event->exception === $exception;
        }));

        $connection = new PhpRedisConnection($client);
        $connection->setEventDispatcher($events);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        $connection->command('get', ['key']);
    }

    public function testCommandExecutedEventIsNotDispatchedWhenCommandFails()
    {
        $exception = new Exception('Test exception');

        $client = m::mock(Redis::class);
        $client->shouldReceive('get')->with('key')->andThrow($exception);

        $events = m::mock(Dispatcher::class);
        $events->shouldReceive('dispatch')->once()->with(m::type(CommandFailed::class));
        $events->shouldNotReceive('dispatch')->with(m::type(CommandExecuted::class));

        $connection = new PhpRedisConnection($client);
        $connection->setEventDispatcher($events);

        try {
            $connection->command('get', ['key']);
            $this->fail('Expected exception was not thrown.');
        } catch (Exception $e) {
            $this->assertSame('Test exception', $e->getMessage());
        }
    }

    public function testCommandFailedEventContainsConnectionName()
    {
        $exception = new Exception('Test exception');

        $client = m::mock(Redis::class);
        $client->shouldReceive('get')->with('key')->andThrow($exception);

        $events = m::mock(Dispatcher::class);
        $events->shouldReceive('dispatch')->once()->with(m::on(function ($event) {
            return $event instanceof CommandFailed
                && $event->connectionName === 'test-connection';
        }));

        $connection = new PhpRedisConnection($client);
        $connection->setName('test-connection');
        $connection->setEventDispatcher($events);

        try {
            $connection->command('get', ['key']);
            $this->fail('Expected exception was not thrown.');
        } catch (Exception $e) {
            $this->assertSame('Test exception', $e->getMessage());
        }
    }

    public function testListenForFailuresRegistersCallback()
    {
        $client = m::mock(Redis::class);

        $events = m::mock(Dispatcher::class);
        $events->shouldReceive('listen')->once()->with(CommandFailed::class, m::type('Closure'));

        $connection = new PhpRedisConnection($client);
        $connection->setEventDispatcher($events);

        $connection->listenForFailures(function () {
            // callback
        });

        $this->addToAssertionCount(1);
    }
}
