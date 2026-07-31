<?php

namespace DeptOfScrapyardRobotics\Tests\UX;

use Fabricate\Contracts\Actuation\HumanInput\ButtonInput;

/**
 * A switch a test can hold down, so the real BasicButton edge detection runs
 * rather than being mocked away — press and release edges are exactly what
 * focus traversal is built on.
 */
final class StubButtonInput implements ButtonInput
{
    public bool $down = false;

    public function isDown(): bool
    {
        return $this->down;
    }

    public function close(): void
    {
        //
    }
}
