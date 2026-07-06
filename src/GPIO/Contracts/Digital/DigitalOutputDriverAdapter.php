<?php

namespace GPIO\Contracts\Digital;

use GPIO\Contracts\Digital\DigitalPinDriverAdapter as DigitalPinDriverAdapterContract;
use GPIO\Contracts\Digital\DigitalPinConnectionHandle as DigitalPinConnectionHandleInterface;

interface DigitalOutputDriverAdapter extends DigitalPinDriverAdapterContract
{
    public function buildConnection(
        int|string $device,
        int $pin,
        string $consumer,
        array $addl_pins = [],
        bool $default_state = false,
    ): DigitalPinTransport|DigitalPinBus;

    public function write(int $pin, bool $state, DigitalPinConnectionHandleInterface $handle): bool;
}
