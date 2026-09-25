<?php

namespace ScrapyardIO\Tests\Fixtures;

use GeneralPurposeIO\Contracts\I2C\I2CTransport;
use GeneralPurposeIO\Contracts\NutsAndBolts\BusJob;
use GeneralPurposeIO\Contracts\NutsAndBolts\GPIOTransport;

/** A chip-level job: answers the address of the slave it ran against. */
final class EchoAddressJob implements BusJob
{
    public function run(GPIOTransport $bus): int
    {
        /** @var I2CTransport $bus */
        return $bus->address();
    }
}
