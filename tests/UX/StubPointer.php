<?php

namespace DeptOfScrapyardRobotics\Tests\UX;

use Fabricate\Actuation\HumanInput\BasicButton;
use Fabricate\Actuation\HumanInput\ButtonPad;
use Fabricate\Contracts\Actuation\HumanInput\CoordinateSpace;
use Fabricate\Contracts\Actuation\HumanInput\Pointer;

/**
 * A pointer whose position a test can set.
 *
 * Its button is labelled '1' rather than 'left', matching the SDL mouse driver,
 * which names buttons by number — the router must not depend on a label it
 * cannot know.
 */
final class StubPointer extends ButtonPad implements Pointer
{
    public StubButtonInput $primary;

    protected float $x = 0.0;

    protected float $y = 0.0;

    public function __construct(protected CoordinateSpace $space = CoordinateSpace::PIXELS)
    {
        $this->primary = new StubButtonInput;

        parent::__construct([new BasicButton('1', $this->primary, hold_ms: 0)]);
    }

    public function at(float $x, float $y): static
    {
        $this->x = $x;
        $this->y = $y;

        return $this;
    }

    public function press(bool $down = true): static
    {
        $this->primary->down = $down;

        $this->poll();

        return $this;
    }

    public function coordinateSpace(): CoordinateSpace
    {
        return $this->space;
    }

    public function x(): float
    {
        return $this->x;
    }

    public function y(): float
    {
        return $this->y;
    }

    public function deltaX(): float
    {
        return 0.0;
    }

    public function deltaY(): float
    {
        return 0.0;
    }

    public function wheelX(): float
    {
        return 0.0;
    }

    public function wheelY(): float
    {
        return 0.0;
    }
}
