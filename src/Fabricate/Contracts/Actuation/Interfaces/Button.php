<?php

namespace Fabricate\Contracts\Actuation\Interfaces;

use Fabricate\Contracts\Circuits\IntegratedCircuit;

/**
 * Pollable button with edge and hold state.
 */
interface Button extends IntegratedCircuit
{
    public function label(): string;

    public function poll(): static;

    public function isDown(): bool;

    public function isPressed(): bool;

    public function wasReleased(): bool;

    public function isHolding(): bool;

    public function heldMs(): int;

    public function holdMs(): int;

    public function setHoldMs(int $hold_ms): static;

    /**
     * @return list<array{down: bool, pressed: bool, released: bool, holding: bool, at_ns: int}>
     */
    public function history(): array;

    public function clearHistory(): static;
}
