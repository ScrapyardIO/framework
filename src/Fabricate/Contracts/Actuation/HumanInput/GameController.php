<?php

namespace Fabricate\Contracts\Actuation\HumanInput;

use Fabricate\Contracts\Actuation\Interfaces\ButtonPad;

/**
 * Standard game-controller buttons and normalized axes.
 */
interface GameController extends ButtonPad
{
    public function connected(): bool;

    /**
     * Return a normalized axis value (-1.0 to 1.0; triggers use 0.0 to 1.0).
     */
    public function axis(GameControllerAxis $axis): float;

    /**
     * @return array<string, float>
     */
    public function axes(): array;
}
