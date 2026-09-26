<?php

namespace ScrapyardIO\Tests\Fixtures;

use Closure;
use GeneralPurposeIO\PWM\PWMConnectionDriver;
use GeneralPurposeIO\PWM\PWMConnectionFactory;

/** Channels on a named board: two drivers on one board see the same attributes, as two processes see one sysfs. */
class FakePWMConnectionDriver extends PWMConnectionDriver
{
    /** Runs while a channel is being opened, standing in for an adapter that waits for the channel to come up. */
    public ?Closure $opening = null;

    public function __construct(
        public readonly string $board = 'bench',
    ) {
        parent::__construct();
    }

    public function workerArguments(): array
    {
        return [$this->board];
    }

    protected function newConnection(int|string $device): PWMConnectionFactory
    {
        return new FakePWMConnectionFactory($device, $this);
    }

    protected function getTransport(string|int $device, int $channel): FakePWMTransport
    {
        $transport = new FakePWMTransport($channel, $this->board, $this->connections->get($device));

        if (! is_null($this->opening)) {
            ($this->opening)();
        }

        return $transport;
    }

    /** @param FakePWMHandle $handle */
    protected function closeConnection(mixed $handle): void
    {
        $handle->closed = true;
    }
}
