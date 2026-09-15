<?php

namespace ScrapyardIO\Tests\Support\Fixtures;

use GeneralPurposeIO\Contracts\Circuits\Attributes\IntegratedCircuit as IntegratedCircuitAttribute;
use GeneralPurposeIO\Contracts\Circuits\IntegratedCircuit;

#[IntegratedCircuitAttribute('I2C', ['SPI', 'DigitalIO'])]
class CircuitRegistryDemoIc implements IntegratedCircuit
{
    public function __construct(
        public readonly string $via = '',
        public readonly array $args = [],
    ) {}

    public function close(): void {}

    public static function i2c(
        string|int $device,
        ?string $adapter = null,
        int $slave = 0x38,
        bool $boot_now = true,
    ): static {
        return new static('i2c', compact('device', 'adapter', 'slave', 'boot_now'));
    }

    public static function spi(
        string|int $spi_device,
        string|int $chip_select,
        string|int $digital_device,
        int $dc_pin,
        int $rst_pin,
        ?string $spi_adapter = null,
        ?string $digital_adapter = null,
        bool $boot_now = true,
    ): static {
        return new static('spi', compact(
            'spi_device',
            'chip_select',
            'digital_device',
            'dc_pin',
            'rst_pin',
            'spi_adapter',
            'digital_adapter',
            'boot_now',
        ));
    }
}
