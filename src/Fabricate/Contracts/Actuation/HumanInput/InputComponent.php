<?php

namespace Fabricate\Contracts\Actuation\HumanInput;

interface InputComponent
{
    public function label(): string;

    public function button(): BasicInputFunctionality;

    public function poll(): static;

    public function isDown(): bool;

    public function isPressed(): bool;

    public function wasReleased(): bool;

    public function isHolding(): bool;

    public function heldMs(): int;
}
