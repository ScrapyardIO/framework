<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital;

use GPIO\Contracts\Common\GPIOConnectionHandle as GPIOConnectionHandleInterface;
use GPIO\Contracts\Digital\DigitalOutputDriverAdapter;
use GPIO\Contracts\Digital\DigitalPinBus;
use GPIO\Contracts\Digital\DigitalPinConnectionHandle as DigitalPinConnectionHandleInterface;
use GPIO\Contracts\Digital\DigitalPinTransport;

class FakeDigitalOutputDriverAdapter implements DigitalOutputDriverAdapter
{
    /** Value returned by every call to read(), unless overridden per-test. */
    public bool $readReturnValue = false;

    /** Every [$pin, $handle] pair passed to read(). */
    public array $readCalls = [];

    /** Every [$pin, $state, $handle] triple passed to write(). */
    public array $writeCalls = [];

    /** Every handle passed to close(). */
    public array $closeCalls = [];

    public function buildConnection(
        int|string $device,
        int $pin,
        string $consumer,
        array $addl_pins = [],
        bool $default_state = false,
    ): DigitalPinTransport|DigitalPinBus {
        return new FakeDigitalConnectionBus([
            'device' => $device,
            'pin' => $pin,
            'consumer' => $consumer,
            'addl_pins' => $addl_pins,
            'default_state' => $default_state,
        ]);
    }

    public function read(int $pin, DigitalPinConnectionHandleInterface $handle): bool
    {
        $this->readCalls[] = [$pin, $handle];

        return $this->readReturnValue;
    }

    public function write(int $pin, bool $state, DigitalPinConnectionHandleInterface $handle): bool
    {
        $this->writeCalls[] = [$pin, $state, $handle];

        return true;
    }

    public function close(GPIOConnectionHandleInterface $handle): void
    {
        $this->closeCalls[] = $handle;
    }
}
