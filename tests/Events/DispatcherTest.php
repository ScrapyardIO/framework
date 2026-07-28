<?php

namespace DeptOfScrapyardRobotics\Tests\Events;

use Fabricate\Chassis\Chassis;
use Fabricate\Events\Dispatcher;
use PHPUnit\Framework\TestCase;

class DispatcherTest extends TestCase
{
    public function testStringEventsPassPayloadToListenersAndCollectResponses(): void
    {
        $dispatcher = new Dispatcher(new Chassis());
        $dispatcher->listen('machine.started', fn (string $machine) => strtoupper($machine));
        $dispatcher->listen('machine.started', fn (string $machine) => strlen($machine));

        $this->assertTrue($dispatcher->hasListeners('machine.started'));
        $this->assertSame(['SCRAPYARD', 9], $dispatcher->dispatch('machine.started', ['scrapyard']));
    }

    public function testObjectEventsAndTheirInterfacesAreDispatched(): void
    {
        $dispatcher = new Dispatcher(new Chassis());
        $received = [];

        $dispatcher->listen(MachineEvent::class, function (MachineStarted $event) use (&$received): void {
            $received[] = 'interface:'.$event->name;
        });
        $dispatcher->listen(function (MachineStarted $event) use (&$received): void {
            $received[] = 'object:'.$event->name;
        });

        $dispatcher->dispatch(new MachineStarted('scrapyard'));

        $this->assertSame(['object:scrapyard', 'interface:scrapyard'], $received);
    }

    public function testUntilAndHaltStopAtTheFirstNonNullResponse(): void
    {
        $dispatcher = new Dispatcher(new Chassis());
        $calls = [];
        $dispatcher->listen('inspect', function () use (&$calls): void {
            $calls[] = 'first';
        });
        $dispatcher->listen('inspect', function () use (&$calls): string {
            $calls[] = 'second';

            return 'result';
        });
        $dispatcher->listen('inspect', function () use (&$calls): string {
            $calls[] = 'third';

            return 'ignored';
        });

        $this->assertSame('result', $dispatcher->until('inspect'));
        $this->assertSame(['first', 'second'], $calls);

        $calls = [];

        $this->assertSame(['result'], $dispatcher->dispatch('inspect', halt: true));
        $this->assertSame(['first', 'second'], $calls);
    }

    public function testFalseResponseStopsNormalDispatch(): void
    {
        $dispatcher = new Dispatcher(new Chassis());
        $calls = [];
        $dispatcher->listen('stop', function () use (&$calls): false {
            $calls[] = 'first';

            return false;
        });
        $dispatcher->listen('stop', function () use (&$calls): void {
            $calls[] = 'second';
        });

        $this->assertSame([], $dispatcher->dispatch('stop'));
        $this->assertSame(['first'], $calls);
    }

    public function testWildcardListenersReceiveTheEventNameAndPayload(): void
    {
        $dispatcher = new Dispatcher(new Chassis());
        $received = [];
        $dispatcher->listen('machine.*', function (string $event, array $payload) use (&$received): void {
            $received = [$event, $payload];
        });

        $dispatcher->dispatch('machine.started', ['scrapyard']);

        $this->assertTrue($dispatcher->hasListeners('machine.started'));
        $this->assertSame(['machine.started', ['scrapyard']], $received);

        $dispatcher->forget('machine.*');

        $this->assertFalse($dispatcher->hasListeners('machine.started'));
    }

    public function testClassListenersAndSubscribersResolveThroughTheContainer(): void
    {
        $container = new Chassis();
        $listener = new RecordingListener();
        $container->instance(RecordingListener::class, $listener);

        $dispatcher = new Dispatcher($container);
        $dispatcher->listen('machine.started', RecordingListener::class);
        $dispatcher->subscribe(new MachineSubscriber($listener));

        $dispatcher->dispatch('machine.started', ['scrapyard']);
        $dispatcher->dispatch('machine.stopped', ['scrapyard']);

        $this->assertSame([
            'handled:scrapyard',
            'subscribed:scrapyard',
        ], $listener->events);
    }

    public function testPushedEventsDispatchWhenFlushedAndCanBeForgotten(): void
    {
        $dispatcher = new Dispatcher(new Chassis());
        $received = [];
        $dispatcher->listen('queued', function (string $value) use (&$received): void {
            $received[] = $value;
        });
        $dispatcher->push('queued', ['first']);
        $dispatcher->push('queued', ['second']);

        $this->assertSame([], $received);

        $dispatcher->flush('queued');

        $this->assertSame(['first', 'second'], $received);

        $dispatcher->forgetPushed();
        $dispatcher->flush('queued');

        $this->assertSame(['first', 'second'], $received);
    }
}

interface MachineEvent
{
}

class MachineStarted implements MachineEvent
{
    public function __construct(public string $name)
    {
    }
}

class RecordingListener
{
    public array $events = [];

    public function handle(string $machine): void
    {
        $this->events[] = 'handled:'.$machine;
    }
}

class MachineSubscriber
{
    public function __construct(private RecordingListener $listener)
    {
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            'machine.stopped' => 'onStopped',
        ];
    }

    public function onStopped(string $machine): void
    {
        $this->listener->events[] = 'subscribed:'.$machine;
    }
}
