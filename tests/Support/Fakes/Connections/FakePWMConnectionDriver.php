<?php

namespace ScrapyardIO\Tests\Support\Fakes\Connections;

use GeneralPurposeIO\Contracts\PWM\PWMTransport;
use GeneralPurposeIO\PWM\PWMConnectionDriver;
use GeneralPurposeIO\PWM\PWMConnectionFactory;

class FakePWMConnectionDriver extends PWMConnectionDriver
{
    protected function newConnection(int|string $device): PWMConnectionFactory
    {
        return new FakePWMConnectionFactory($device, $this);
    }

    protected function getTransport(string|int $device, int $channel): PWMTransport
    {
        return new FakePWMTransport($channel, $this->connections->get($device));
    }
}
