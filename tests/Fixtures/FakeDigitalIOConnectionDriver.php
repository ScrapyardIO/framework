<?php

namespace ScrapyardIO\Tests\Fixtures;

use GeneralPurposeIO\Contracts\Digital\LineBias;
use GeneralPurposeIO\Digital\DigitalIOConnectionDriver;
use GeneralPurposeIO\Digital\DigitalIOConnectionFactory;

final class FakeDigitalIOConnectionDriver extends DigitalIOConnectionDriver
{
    /** @var list<mixed> */
    public array $closed_connections = [];

    protected function newConnection(int|string $device): DigitalIOConnectionFactory
    {
        return new FakeDigitalIOConnectionFactory($device, $this);
    }

    protected function getOutputTransport(string|int $device, int $pin): FakeDigitalOutputTransport
    {
        return $this->pins["{$device}:{$pin}"] ??= new FakeDigitalOutputTransport($pin);
    }

    protected function getInputTransport(string|int $device, int $pin, LineBias $bias = LineBias::AS_IS, bool $active_low = false): SocketDigitalInputTransport
    {
        return $this->pins["{$device}:{$pin}"] ??= new SocketDigitalInputTransport($pin);
    }

    protected function closeConnection(mixed $handle): void
    {
        $this->closed_connections[] = $handle;
    }
}
