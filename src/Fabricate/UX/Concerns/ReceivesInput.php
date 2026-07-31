<?php

namespace Fabricate\UX\Concerns;

use Fabricate\Contracts\Actuation\HumanInput\TouchContact;
use Fabricate\NutsAndBolts\Geometry\Point;

/**
 * Everything {@see \Fabricate\Contracts\UX\InputTarget} needs except the part
 * that is actually about the node.
 *
 * A node using this is rectangular, focusable, and ignores every event until it
 * overrides the handler it cares about. Declining by default is the safe
 * direction: an unhandled event carries on to nothing, whereas a node that
 * silently claimed events it does not use would swallow gestures meant for
 * whatever sits beneath it.
 *
 * Focus is held as node state and invalidates on change, so a focus ring
 * repaints without the node having to remember to ask.
 */
trait ReceivesInput
{
    protected bool $focusable = true;

    protected bool $focused = false;

    /**
     * Local coordinates are already known to be inside the bounds, so a
     * rectangular node has nothing left to check. A round gauge overrides this.
     */
    public function hitTest(Point $local): bool
    {
        return true;
    }

    public function acceptsFocus(): bool
    {
        return $this->focusable && $this->isVisible();
    }

    /**
     * Drop out of traversal without leaving a gap, which is what a disabled or
     * purely decorative interactive node wants.
     */
    public function focusable(bool $focusable = true): static
    {
        $this->focusable = $focusable;

        return $this;
    }

    public function isFocused(): bool
    {
        return $this->focused;
    }

    public function onTouch(TouchContact $contact, Point $local): bool
    {
        return false;
    }

    public function onPointer(Point $local, bool $pressed): bool
    {
        return false;
    }

    public function onButton(string $label): bool
    {
        return false;
    }

    public function onFocusGained(): void
    {
        $this->setFocused(true);
    }

    public function onFocusLost(): void
    {
        $this->setFocused(false);
    }

    protected function setFocused(bool $focused): void
    {
        if ($this->focused === $focused) {
            return;
        }

        $this->focused = $focused;

        $this->invalidate();
    }
}
