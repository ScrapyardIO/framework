<?php

namespace ScrapyardIO\Tests\Fixtures;

use GeneralPurposeIO\Contracts\NutsAndBolts\BusJob;
use GeneralPurposeIO\Contracts\NutsAndBolts\GPIOTransport;
use GeneralPurposeIO\Contracts\SPI\SPITransport;

/** A chip-level job: answers the chip select of the slave it ran against. */
final class EchoChipSelectJob implements BusJob
{
    public function run(GPIOTransport $bus): int
    {
        /** @var SPITransport $bus */
        return $bus->chipSelect();
    }
}
