<?php

namespace GeneralPurposeIO\Contracts\IntegratedCircuits;

/**
 * The chip catalog. A chip package says what it ships; an app wires it in
 * config/circuits/<slug>.php; conjure() hands back the live chip.
 */
interface CircuitRegistry
{
    /** Catalog a chip type under a slug. A class that is not an IntegratedCircuit is ignored. */
    public function addCircuit(string $slug, string $class_name): void;

    public function has(string $slug): bool;

    /** @return array<string, class-string<IntegratedCircuit>> */
    public function listCircuits(): array;

    /** @return class-string<IntegratedCircuit> */
    public function resolveClass(string $slug): string;

    /**
     * Build the chip from config('circuits.<slug>.configs.<config>'); null
     * means the chip's default_config.
     */
    public function conjure(string $slug, ?string $config = null): IntegratedCircuit;

    /** @param array<string, mixed> $params */
    public function build(string $slug, string $protocol, array $params): IntegratedCircuit;
}
