<?php

namespace DeptOfScrapyardRobotics\Tests\UX;

use Closure;
use Fabricate\Contracts\Actuation\HumanInput\TouchContact;
use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\Contracts\UX\InputTarget;
use Fabricate\NutsAndBolts\Geometry\Point;
use Fabricate\UX\Concerns\ReceivesInput;
use Fabricate\UX\Node;

/**
 * A node that takes input and records what it received.
 *
 * Handlers claim their events by default, which is the opposite of the trait's
 * default: routing only stops at a node that says it consumed the event, so a
 * fixture that declined everything would make "reached exactly one node"
 * impossible to assert.
 */
class InteractiveNode extends Node implements InputTarget
{
    use ReceivesInput;

    /**
     * @var array<int, array{0: int, 1: int}>
     */
    public array $touches = [];

    /**
     * @var array<int, array{0: int, 1: int, 2: bool}>
     */
    public array $pointers = [];

    /**
     * @var array<int, string>
     */
    public array $buttons = [];

    public int $focus_gained = 0;

    public int $focus_lost = 0;

    /**
     * Narrows the hittable area within the bounds, for the round-gauge case.
     *
     * @var ?Closure(Point): bool
     */
    public ?Closure $hit_shape = null;

    public function __construct(
        public readonly string $name = 'node',
        int $x = 0,
        int $y = 0,
        int $width = 0,
        int $height = 0,
    ) {
        parent::__construct($x, $y, $width, $height);
    }

    public function paint(DrawingSurface $surface): void
    {
        //
    }

    public function hitTest(Point $local): bool
    {
        return is_null($this->hit_shape) ? true : ($this->hit_shape)($local);
    }

    public function onTouch(TouchContact $contact, Point $local): bool
    {
        $this->touches[] = [$local->x, $local->y];

        return true;
    }

    public function onPointer(Point $local, bool $pressed): bool
    {
        $this->pointers[] = [$local->x, $local->y, $pressed];

        return true;
    }

    public function onButton(string $label): bool
    {
        $this->buttons[] = $label;

        return true;
    }

    public function onFocusGained(): void
    {
        $this->focus_gained++;

        $this->setFocused(true);
    }

    public function onFocusLost(): void
    {
        $this->focus_lost++;

        $this->setFocused(false);
    }
}
