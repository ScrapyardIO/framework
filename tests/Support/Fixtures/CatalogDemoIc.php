<?php

namespace ScrapyardIO\Tests\Support\Fixtures;

use GeneralPurposeIO\Contracts\IntegratedCircuits\IntegratedCircuit;

/** A chip whose factories take the keys a config/circuits/<slug>.php entry carries. */
class CatalogDemoIc implements IntegratedCircuit
{
    /** @param array<string, mixed> $args */
    public function __construct(
        public readonly string $via = '',
        public readonly array $args = [],
    ) {}

    public static function i2c(string $driver, string|int $device, int $slave = 0x38, bool $boot_now = true): static
    {
        return new static('i2c', compact('driver', 'device', 'slave', 'boot_now'));
    }

    /**
     * @param  array{driver: string, device: string|int, pin: int}  $dc
     * @param  array{driver: string, device: string|int, pin: int}  $rst
     */
    public static function spi(string $driver, string|int $device, array $dc, array $rst, int $chip_select = 0, bool $boot_now = true): static
    {
        return new static('spi', compact('driver', 'device', 'dc', 'rst', 'chip_select', 'boot_now'));
    }

    /** Not static: the registry must refuse it as a protocol factory. */
    public function uart(): static
    {
        return $this;
    }

    /** Static, public, but hands back something that is not a circuit. */
    public static function bogus(): string
    {
        return 'not a circuit';
    }
}
