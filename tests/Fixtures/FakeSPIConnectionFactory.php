<?php

namespace ScrapyardIO\Tests\Fixtures;

use GeneralPurposeIO\SPI\SPIConnectionFactory;

final class FakeSPIConnectionFactory extends SPIConnectionFactory
{
    public function chipSelect(int $chip_select): static
    {
        $this->chip_select = $chip_select;

        return $this;
    }

    protected function device(): string|int
    {
        return $this->device;
    }

    public function getHandle(): FakeSPIHandle
    {
        return new FakeSPIHandle($this->device);
    }
}
