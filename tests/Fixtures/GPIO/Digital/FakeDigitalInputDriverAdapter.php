<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital;

use GPIO\Contracts\Common\GPIOConnectionHandle as GPIOConnectionHandleInterface;
use GPIO\Contracts\Digital\DigitalInputDriverAdapter;
use GPIO\Contracts\Digital\DigitalInputEvent as DigitalInputEventInterface;
use GPIO\Contracts\Digital\DigitalPinBus;
use GPIO\Contracts\Digital\DigitalPinConnectionHandle as DigitalPinConnectionHandleInterface;
use GPIO\Contracts\Digital\DigitalPinTransport;
use GPIO\Contracts\Digital\LineBias;

class FakeDigitalInputDriverAdapter implements DigitalInputDriverAdapter
{
    /** Value returned by every call to read(), unless overridden per-test. */
    public bool $readReturnValue = false;

    /** Every [$pin, $handle] pair passed to read(). */
    public array $readCalls = [];

    /** Value returned by every call to listen(), unless overridden per-test. */
    public ?DigitalInputEventInterface $listenReturnValue = null;

    /** Every argument list passed to listen(). */
    public array $listenCalls = [];

    /** Every handle passed to close(). */
    public array $closeCalls = [];

    public function buildConnection(
        int|string $device,
        int $pin,
        string $consumer,
        array $addl_pins = [],
        int $timeout = 0,
        bool $rising_events = false,
        bool $falling_events = false,
        LineBias $line_bias = LineBias::AS_IS,
        bool $active_low = false,
    ): DigitalPinTransport|DigitalPinBus {
        return new FakeDigitalConnectionBus([
            'device' => $device,
            'pin' => $pin,
            'consumer' => $consumer,
            'addl_pins' => $addl_pins,
            'timeout' => $timeout,
            'rising_events' => $rising_events,
            'falling_events' => $falling_events,
            'line_bias' => $line_bias,
            'active_low' => $active_low,
        ]);
    }

    public function read(int $pin, DigitalPinConnectionHandleInterface $handle): bool
    {
        $this->readCalls[] = [$pin, $handle];

        return $this->readReturnValue;
    }

    public function listen(int $timeout, bool $rising_events, bool $falling_events, int $pin, DigitalPinConnectionHandleInterface $handle): ?DigitalInputEventInterface
    {
        $this->listenCalls[] = [$timeout, $rising_events, $falling_events, $pin, $handle];

        return $this->listenReturnValue;
    }

    public function close(GPIOConnectionHandleInterface $handle): void
    {
        $this->closeCalls[] = $handle;
    }
}
