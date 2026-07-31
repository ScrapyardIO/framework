<?php

namespace Fabricate\UX\Input;

use Fabricate\Contracts\Actuation\HumanInput\GameControllerButton;
use Fabricate\Contracts\UX\Enums\FocusDirection;

/**
 * Which button labels drive focus traversal, and which mean "act on the focused
 * node".
 *
 * Labels rather than an enum of abstract actions, because that is what
 * {@see \Fabricate\Contracts\Actuation\Interfaces\ButtonPad} deals in and a
 * physical `generic-buttons` pad is free to call its buttons whatever the
 * wiring says. The defaults cover the {@see GameControllerButton} vocabulary and
 * the plain directional names a hand-wired pad tends to use, so a controller, a
 * keyboard and four tactile switches all navigate without configuration.
 */
final readonly class NavigationBindings
{
    /**
     * @param  array<string, FocusDirection>  $traversal
     * @param  array<int, string>  $activation
     */
    public function __construct(
        public array $traversal,
        public array $activation,
    ) {}

    public static function default(): self
    {
        return new self(
            [
                GameControllerButton::DPAD_DOWN->value => FocusDirection::NEXT,
                GameControllerButton::DPAD_RIGHT->value => FocusDirection::NEXT,
                GameControllerButton::DPAD_UP->value => FocusDirection::PREVIOUS,
                GameControllerButton::DPAD_LEFT->value => FocusDirection::PREVIOUS,
                'down' => FocusDirection::NEXT,
                'right' => FocusDirection::NEXT,
                'next' => FocusDirection::NEXT,
                'up' => FocusDirection::PREVIOUS,
                'left' => FocusDirection::PREVIOUS,
                'previous' => FocusDirection::PREVIOUS,
            ],
            [
                GameControllerButton::SOUTH->value,
                GameControllerButton::START->value,
                'a',
                'ok',
                'enter',
                'select',
            ],
        );
    }

    public function traverses(string $label): ?FocusDirection
    {
        return $this->traversal[$label] ?? null;
    }

    /**
     * Whether $label is the "do it" button. The router still forwards the raw
     * label to the node, so a node that cares which button it was can tell.
     */
    public function activates(string $label): bool
    {
        return in_array($label, $this->activation, true);
    }

    /**
     * @param  array<string, FocusDirection>  $traversal
     */
    public function withTraversal(array $traversal): self
    {
        return new self($traversal, $this->activation);
    }

    /**
     * @param  array<int, string>  $activation
     */
    public function withActivation(array $activation): self
    {
        return new self($this->traversal, array_values($activation));
    }
}
