<?php

namespace GPIO\Contracts\Digital;

use GPIO\Contracts\Digital\DigitalInputEvent as DigitalInputEventInterface;
use GPIO\Contracts\Digital\DigitalPinDriverAdapter as DigitalPinDriverAdapterContract;
use GPIO\Contracts\Digital\DigitalPinConnectionHandle as DigitalPinConnectionHandleInterface;

interface DigitalInputDriverAdapter extends DigitalPinDriverAdapterContract
{
    public function buildConnection(
        int|string $device,
        int $pin,
        string $consumer,
        array $addl_pins = [],
        int $timeout = -0,
        bool $rising_events = false,
        bool $falling_events = false,
        LineBias $line_bias = LineBias::AS_IS,
        bool $active_low = false,

    ): DigitalPinTransport|DigitalPinBus;

    public function listen(int $timeout, bool $rising_events, bool $falling_events,int $pin, DigitalPinConnectionHandleInterface $handle): ?DigitalInputEventInterface;
}
