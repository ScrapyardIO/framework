<?php

namespace Fabricate\Actuation\Components;

use Fabricate\Contracts\Actuation\HumanInput\BasicInputFunctionality;

class LatchedInput implements BasicInputFunctionality
{
    public function __construct(
        protected bool $down = false,
    ) {}

    public function latch(bool $down): void
    {
        $this->down = $down;
    }

    public function isDown(): bool
    {
        return $this->down;
    }
}
