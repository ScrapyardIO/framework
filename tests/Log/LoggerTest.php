<?php

namespace DeptOfScrapyardRobotics\Tests\Log;

use Fabricate\Chassis\Chassis;
use Fabricate\Events\Dispatcher;
use Fabricate\Log\Events\MessageLogged;
use Fabricate\Log\Logger;
use Monolog\Handler\TestHandler;
use Monolog\Logger as Monolog;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LoggerTest extends TestCase
{
    public function testItWritesMessagesAndMergesSharedContext(): void
    {
        $handler = new TestHandler();
        $logger = new Logger(new Monolog('testing', [$handler]));
        $logger->withContext(['machine' => 'scrapyard']);

        $logger->info('Machine started', ['environment' => 'testing']);

        $this->assertTrue($handler->hasInfoThatContains('Machine started'));
        $record = $handler->getRecords()[0];
        $this->assertSame([
            'machine' => 'scrapyard',
            'environment' => 'testing',
        ], $record->context);
    }

    public function testSharedContextCanBeRemovedSelectivelyOrCompletely(): void
    {
        $handler = new TestHandler();
        $logger = new Logger(new Monolog('testing', [$handler]));
        $logger->withContext(['machine' => 'scrapyard', 'environment' => 'testing'])
            ->withoutContext(['environment']);

        $logger->debug('First');
        $logger->withoutContext()->debug('Second');

        $this->assertSame(['machine' => 'scrapyard'], $handler->getRecords()[0]->context);
        $this->assertSame([], $handler->getRecords()[1]->context);
    }

    public function testItFormatsArrayMessagesBeforeWriting(): void
    {
        $handler = new TestHandler();
        $logger = new Logger(new Monolog('testing', [$handler]));

        $logger->notice(['machine' => 'scrapyard']);

        $this->assertStringContainsString("'machine' => 'scrapyard'", $handler->getRecords()[0]->message);
    }

    public function testItDispatchesMessageLoggedEvents(): void
    {
        $dispatcher = new Dispatcher(new Chassis());
        $handler = new TestHandler();
        $logger = new Logger(new Monolog('testing', [$handler]), $dispatcher);
        $event = null;
        $logger->listen(function (MessageLogged $messageLogged) use (&$event): void {
            $event = $messageLogged;
        });

        $logger->warning('Machine warning', ['machine' => 'scrapyard']);

        $this->assertInstanceOf(MessageLogged::class, $event);
        $this->assertSame('warning', $event->level);
        $this->assertSame('Machine warning', $event->message);
        $this->assertSame(['machine' => 'scrapyard'], $event->context);
    }

    public function testListeningWithoutAnEventDispatcherIsRejected(): void
    {
        $logger = new Logger(new Monolog('testing', [new TestHandler()]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Events dispatcher has not been set.');

        $logger->listen(fn (MessageLogged $event) => null);
    }
}
