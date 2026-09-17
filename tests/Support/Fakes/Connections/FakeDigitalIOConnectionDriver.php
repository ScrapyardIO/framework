<?php

namespace ScrapyardIO\Tests\Support\Fakes\Connections;

use GeneralPurposeIO\Contracts\Digital\LineBias;
use GeneralPurposeIO\Digital\DigitalInputTransport;
use GeneralPurposeIO\Digital\DigitalIOConnectionDriver;
use GeneralPurposeIO\Digital\DigitalIOConnectionFactory;
use GeneralPurposeIO\Digital\DigitalOutputTransport;

class FakeDigitalIOConnectionDriver extends DigitalIOConnectionDriver
{
    protected function newConnection(int|string $device): DigitalIOConnectionFactory
    {
        return new FakeDigitalIOConnectionFactory($device, $this);
    }

    protected function getOutputTransport(string|int $device, int $pin): DigitalOutputTransport
    {
        return new FakeDigitalOutputTransport($pin, $this->connections->get($device));
    }

    protected function getInputTransport(string|int $device, int $pin, LineBias $bias = LineBias::AS_IS, bool $active_low = false): DigitalInputTransport
    {
        return new FakeDigitalInputTransport($pin, $this->connections->get($device), $bias, $active_low);
    }
}
