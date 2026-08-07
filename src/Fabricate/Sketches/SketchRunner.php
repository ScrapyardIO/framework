<?php

namespace Fabricate\Sketches;

use Fabricate\Contracts\Sketches\Sketch;
use Fabricate\Contracts\Sketches\SketchExitStatus;
use Fabricate\Sketches\Flow\BootSketchNode;
use Fabricate\Sketches\Flow\Flow;
use Fabricate\Sketches\Flow\TickSketchNode;
use Throwable;

class SketchRunner
{
    /**
     * Indicates whether a cooperative stop has been requested.
     */
    protected bool $shouldStop = false;

    /**
     * Indicates whether shutdown() has already been invoked for the active run.
     */
    protected bool $shutdownInvoked = false;

    /**
     * Run a Sketch through a lifecycle Flow (boot → tick self-loop).
     *
     * @throws Throwable
     */
    public function run(Sketch $sketch): int
    {
        $this->shouldStop = false;
        $this->shutdownInvoked = false;

        $this->listenForSignals();

        $shared = [
            'runner' => $this,
            'sketch' => $sketch,
        ];

        $boot = new BootSketchNode;
        $tick = new TickSketchNode;
        $boot->next($tick);
        $tick->next($tick, 'continue');

        $flow = new Flow($boot);

        try {
            $flow->run($shared);

            return SketchExitStatus::SUCCESS->value;
        } finally {
            $this->shutdownOnce($sketch);
        }
    }

    /**
     * Request a cooperative stop after the current loop tick.
     */
    public function stop(): void
    {
        $this->shouldStop = true;
    }

    /**
     * Determine whether a cooperative stop has been requested.
     */
    public function shouldStop(): bool
    {
        return $this->shouldStop;
    }

    /**
     * Invoke shutdown exactly once for the active run.
     */
    protected function shutdownOnce(Sketch $sketch): void
    {
        if ($this->shutdownInvoked) {
            return;
        }

        $this->shutdownInvoked = true;
        $sketch->shutdown();
    }

    /**
     * Listen for process termination signals when available.
     */
    protected function listenForSignals(): void
    {
        if (! $this->supportsSignals()) {
            return;
        }

        pcntl_async_signals(true);

        foreach ([SIGINT, SIGTERM] as $signal) {
            pcntl_signal($signal, function (): void {
                $this->stop();
            });
        }
    }

    /**
     * Determine whether async signal handling is available.
     */
    protected function supportsSignals(): bool
    {
        return extension_loaded('pcntl');
    }
}
