<?php

namespace ScrapyardIO\Tests\Fixtures;

use GeneralPurposeIO\PWM\PWMConnectionFactory;

final class FakePWMConnectionFactory extends PWMConnectionFactory
{
    protected function device(): string|int
    {
        return $this->device;
    }

    protected function getHandle(): FakePWMHandle
    {
        return new FakePWMHandle($this->device);
    }
}
