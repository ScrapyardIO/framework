<?php

namespace ScrapyardIO\Tests\Support\Fixtures;

use GeneralPurposeIO\Contracts\Circuits\Attributes\IntegratedCircuit as IntegratedCircuitAttribute;
use GeneralPurposeIO\Contracts\Circuits\IntegratedCircuit;

#[IntegratedCircuitAttribute(['SPI', 'DigitalIO'], 'SPI', 'DigitalIO')]
class CircuitCatalogAboutRowsDemoIc implements IntegratedCircuit
{
    public function close(): void {}
}
