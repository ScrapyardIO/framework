<?php

namespace DeptOfScrapyardRobotics\Tests\Queue;

use Exception;
use Fabricate\Contracts\Queue\Job;
use Fabricate\Queue\InteractsWithQueue;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class InteractsWithQueueTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCreatesAnExceptionFromString()
    {
        $queueJob = m::mock(Job::class);
        $queueJob->shouldReceive('fail')->withArgs(function ($e) {
            $this->assertInstanceOf(Exception::class, $e);
            $this->assertSame('Whoops!', $e->getMessage());

            return true;
        });

        $job = new class
        {
            use InteractsWithQueue;

            public $job;
        };

        $job->job = $queueJob;
        $job->fail('Whoops!');
    }
}
