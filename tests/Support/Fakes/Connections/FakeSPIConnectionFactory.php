<?php

namespace ScrapyardIO\Tests\Support\Fakes\Connections;

use GeneralPurposeIO\SPI\SPIConnectionFactory;

class FakeSPIConnectionFactory extends SPIConnectionFactory
{
    protected function device(): string|int
    {
        return $this->device;
    }

    public function getHandle(): FakeHandle
    {
        return new FakeHandle($this->device);
    }

    public function chipSelect(int $chip_select): static
    {
        $this->chip_select = $chip_select;

        return $this;
    }
}
