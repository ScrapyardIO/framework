<?php

use GeneralPurposeIO\Circuits\CircuitRegistry;
use GeneralPurposeIO\Circuits\Support\CircuitCatalogAboutRows;
use ScrapyardIO\Tests\Support\Fixtures\CircuitCatalogAboutRowsDemoIc;

it('yields no rows for an empty catalog', function () {
    expect(CircuitCatalogAboutRows::for(new CircuitRegistry))->toBe([]);
});

it('maps a catalog slug to option labels', function () {
    $registry = new CircuitRegistry;
    $registry->addCircuit('demo_panel', CircuitCatalogAboutRowsDemoIc::class);

    $rows = CircuitCatalogAboutRows::for($registry);

    expect($rows)->toHaveKey('demo_panel')
        ->and(($rows['demo_panel'])(true))->toBe(['SPI+DigitalIO', 'SPI', 'DigitalIO'])
        ->and(($rows['demo_panel'])(false))->toContain('SPI+DigitalIO')->toContain('SPI')->toContain('DigitalIO');
});
