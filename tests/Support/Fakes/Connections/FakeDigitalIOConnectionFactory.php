<?php

namespace ScrapyardIO\Tests\Support\Fakes\Connections;

use GeneralPurposeIO\Digital\DigitalIOConnectionFactory;

class FakeDigitalIOConnectionFactory extends DigitalIOConnectionFactory
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
