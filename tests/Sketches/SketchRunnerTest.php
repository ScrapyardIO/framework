<?php

namespace DeptOfScrapyardRobotics\Tests\Sketches;

use DeptOfScrapyardRobotics\Tests\Sketches\Fixtures\CountingSketch;
use DeptOfScrapyardRobotics\Tests\Sketches\Fixtures\ExternalStopSketch;
use DeptOfScrapyardRobotics\Tests\Sketches\Fixtures\ThrowingSketch;
use Fabricate\Contracts\Sketches\SketchExitStatus;
use Fabricate\Contracts\Sketches\SketchLoopResult;
use Fabricate\Sketches\Sketch;
use Fabricate\Sketches\SketchRunner;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SketchRunnerTest extends TestCase
{
    public function testLifecycleOrderingAndRepeatedContinueUntilStop(): void
    {
        $sketch = new CountingSketch(3);
        $runner = new SketchRunner;

        $status = $runner->run($sketch);

        $this->assertSame(SketchExitStatus::SUCCESS->value, $status);
        $this->assertSame(3, $sketch->loops);
        $this->assertSame(['boot', 'loop', 'loop', 'loop', 'shutdown'], $sketch->calls);
    }

    public function testExternalStopEndsTheLoopCooperatively(): void
    {
        $runner = new SketchRunner;
        $sketch = new ExternalStopSketch($runner);

        $status = $runner->run($sketch);

        $this->assertSame(SketchExitStatus::SUCCESS->value, $status);
        $this->assertTrue($runner->shouldStop());
        $this->assertSame(['boot', 'loop', 'shutdown'], $sketch->calls);
    }

    public function testSignalHandlerRequestsCooperativeStop(): void
    {
        if (! extension_loaded('pcntl') || ! function_exists('posix_kill')) {
            $this->markTestSkipped('pcntl and posix extensions are required for signal stop coverage.');
        }

        $previousTerm = pcntl_signal_get_handler(SIGTERM);
        $previousInt = pcntl_signal_get_handler(SIGINT);

        try {
            $runner = new SketchRunner;

            $sketch = new class extends Sketch
            {
                /** @var list<string> */
                public array $calls = [];

                public int $loops = 0;

                public function boot(): void
                {
                    $this->calls[] = 'boot';
                }

                public function loop(): SketchLoopResult
                {
                    $this->loops++;
                    $this->calls[] = 'loop';

                    if ($this->loops === 1) {
                        posix_kill(getmypid(), SIGTERM);
                        pcntl_signal_dispatch();
                    }

                    return SketchLoopResult::CONTINUE;
                }

                public function shutdown(): void
                {
                    $this->calls[] = 'shutdown';
                }
            };

            $status = $runner->run($sketch);

            $this->assertTrue($runner->shouldStop());
            $this->assertSame(SketchExitStatus::SUCCESS->value, $status);
            $this->assertSame(1, $sketch->loops);
            $this->assertSame(['boot', 'loop', 'shutdown'], $sketch->calls);
            $this->assertSame(1, count(array_filter($sketch->calls, fn (string $call) => $call === 'shutdown')));
        } finally {
            pcntl_signal(SIGTERM, $previousTerm ?: SIG_DFL);
            pcntl_signal(SIGINT, $previousInt ?: SIG_DFL);
        }
    }

    public function testExceptionsPropagateAfterExactlyOnceShutdown(): void
    {
        $sketch = new ThrowingSketch('loop');
        $runner = new SketchRunner;

        try {
            $runner->run($sketch);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('loop failed', $e->getMessage());
        }

        $this->assertSame(['boot', 'loop', 'shutdown'], $sketch->calls);
    }

    public function testBootExceptionsStillInvokeShutdownOnce(): void
    {
        $sketch = new ThrowingSketch('boot');
        $runner = new SketchRunner;

        try {
            $runner->run($sketch);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('boot failed', $e->getMessage());
        }

        $this->assertSame(['boot', 'shutdown'], $sketch->calls);
    }
}
