<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\BareMetal\Actuation;

use BareMetal\Contracts\Actuators\HumanInput\BasicButtonFunctionality;

/**
 * Controllable BasicButtonFunctionality double for HumanInput component tests.
 */
class FakeBasicButton implements BasicButtonFunctionality
{
    public function __construct(
        public bool $down = false,
    ) {}

    public function press(): void
    {
        $this->down = true;
    }

    public function release(): void
    {
        $this->down = false;
    }

    public function isDown(): bool
    {
        return $this->down;
    }
}
