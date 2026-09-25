<?php

namespace ScrapyardIO\Tests\Fixtures;

use GeneralPurposeIO\Digital\DigitalIOConnectionFactory;

final class FakeDigitalIOConnectionFactory extends DigitalIOConnectionFactory
{
    protected function device(): string|int
    {
        return $this->device;
    }

    protected function getHandle(): string
    {
        return "handle:{$this->device}";
    }
}
