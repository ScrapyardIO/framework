<?php

namespace Fabricate\Contracts\Actuation\HumanInput;

interface InputPad
{
    public function poll(): static;

    /** @return array<string, \Fabricate\Actuation\Components\BasicInput> */
    public function buttons(): array;

    /** @return list<string> */
    public function labels(): array;

    public function button(string $label): \Fabricate\Actuation\Components\BasicInput;

    public function has(string $label): bool;

    public function isDown(string $label): bool;

    public function isPressed(string $label): bool;

    public function wasReleased(string $label): bool;

    public function isHolding(string $label): bool;

    /** @return list<string> */
    public function downLabels(): array;

    /** @return list<string> */
    public function pressedLabels(): array;

    /** @return list<string> */
    public function holdingLabels(): array;

    public function anyDown(string ...$labels): bool;

    public function allDown(string ...$labels): bool;

    public function chord(string ...$labels): bool;

    public function anyPressed(string ...$labels): bool;
}
