<?php

namespace ScrapyardIO\Tests\Support\Fakes\Connections;

use GeneralPurposeIO\PWM\PWMConnectionFactory;

class FakePWMConnectionFactory extends PWMConnectionFactory
{
    protected function device(): string|int
    {
        return $this->device;
    }

    protected function getHandle(): FakeHandle
    {
        return new FakeHandle($this->device);
    }
}
