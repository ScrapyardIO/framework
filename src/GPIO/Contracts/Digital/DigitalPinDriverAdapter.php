<?php

namespace GPIO\Contracts\Digital;

use GPIO\Contracts\Common\GPIODriverAdapter as GPIODriverAdapterContract;
use GPIO\Contracts\Digital\DigitalPinConnectionHandle as DigitalPinConnectionHandleInterface;

interface DigitalPinDriverAdapter extends GPIODriverAdapterContract
{
    public function buildConnection(
        int|string $device,
        int $pin,
        string $consumer,
    ): DigitalPinTransport|DigitalPinBus;

    public function read(int $pin, DigitalPinConnectionHandleInterface $handle): bool;
}
