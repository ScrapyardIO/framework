<?php

use Fabricate\Contracts\Sketches\SketchExitStatus;
use Fabricate\Contracts\Sketches\SketchLoopResult;
use Fabricate\Sketches\Sketch;
use Fabricate\Sketches\SketchRunner;
use Tests\Sketches\Fixtures\CountingSketch;
use Tests\Sketches\Fixtures\ExternalStopSketch;
use Tests\Sketches\Fixtures\ThrowingSketch;

test('lifecycle boots loops and shuts down via Flow', function () {
    $sketch = new CountingSketch(3);
    $runner = new SketchRunner;

    $status = $runner->run($sketch);

    expect($status)->toBe(SketchExitStatus::SUCCESS->value)
        ->and($sketch->loops)->toBe(3)
        ->and($sketch->calls)->toBe(['boot', 'loop', 'loop', 'loop', 'shutdown']);
});

test('external stop ends the loop cooperatively', function () {
    $runner = new SketchRunner;
    $sketch = new ExternalStopSketch($runner);

    $status = $runner->run($sketch);

    expect($status)->toBe(SketchExitStatus::SUCCESS->value)
        ->and($runner->shouldStop())->toBeTrue()
        ->and($sketch->calls)->toBe(['boot', 'loop', 'shutdown']);
});

test('exceptions propagate after exactly once shutdown', function () {
    $sketch = new ThrowingSketch('loop');
    $runner = new SketchRunner;

    expect(fn () => $runner->run($sketch))
        ->toThrow(RuntimeException::class, 'loop failed');

    expect($sketch->calls)->toBe(['boot', 'loop', 'shutdown']);
});

test('boot exceptions still invoke shutdown once', function () {
    $sketch = new ThrowingSketch('boot');
    $runner = new SketchRunner;

    expect(fn () => $runner->run($sketch))
        ->toThrow(RuntimeException::class, 'boot failed');

    expect($sketch->calls)->toBe(['boot', 'shutdown']);
});

test('signal handler requests cooperative stop', function () {
    if (! extension_loaded('pcntl') || ! function_exists('posix_kill')) {
        $this->markTestSkipped('pcntl and posix required');
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

        expect($runner->shouldStop())->toBeTrue()
            ->and($status)->toBe(SketchExitStatus::SUCCESS->value)
            ->and($sketch->loops)->toBe(1)
            ->and($sketch->calls)->toBe(['boot', 'loop', 'shutdown']);
    } finally {
        pcntl_signal(SIGTERM, $previousTerm ?: SIG_DFL);
        pcntl_signal(SIGINT, $previousInt ?: SIG_DFL);
    }
});
