<?php

namespace Fabricate\UX\Input;

use Fabricate\Contracts\Actuation\HumanInput\CoordinateSpace;
use Fabricate\Contracts\Actuation\HumanInput\Pointer;
use Fabricate\Contracts\Actuation\HumanInput\Touch;
use Fabricate\Contracts\Actuation\HumanInput\TouchContact;
use Fabricate\Contracts\Actuation\HumanInput\TouchPhase;
use Fabricate\Contracts\Actuation\Interfaces\ButtonPad;
use Fabricate\Contracts\UX\InputTarget;
use Fabricate\Contracts\UX\Stage;
use Fabricate\NutsAndBolts\Geometry\Point;

/**
 * Turns HumanInput devices into node callbacks.
 *
 * Everything a device reports is reduced to one of two questions. Touches and
 * pointers ask *where*, and are answered by {@see HitTest}. Buttons ask *what*,
 * and are answered by whichever node currently holds focus. A game controller,
 * a keyboard and four tactile switches wired to `generic-buttons` are all the
 * same {@see ButtonPad} here, so navigation is written once.
 *
 * The router never polls. Devices are polled by the sketch, which usually shares
 * one pad between the UI and its own controls, and polling twice would eat the
 * edge that {@see ButtonPad::isPressed()} exists to report.
 *
 * A gesture belongs to whatever it started on. Once a contact or the pointer goes
 * down on a node, every later phase is delivered to that same node even after the
 * finger has slid off it — see {@see capture()}.
 */
final class InputRouter
{
    protected FocusRing $focus;

    protected NavigationBindings $bindings;

    protected bool $repeat_on_hold = false;

    /**
     * The node each live contact went down on, keyed by contact id.
     *
     * @var array<int|string, InputTarget>
     */
    protected array $captures = [];

    protected ?InputTarget $pointer_capture = null;

    public function __construct(
        protected readonly Stage $stage,
        ?NavigationBindings $bindings = null,
    ) {
        $this->focus = new FocusRing($stage);
        $this->bindings = $bindings ?? NavigationBindings::default();
    }

    public function focus(): FocusRing
    {
        return $this->focus;
    }

    public function bindings(): NavigationBindings
    {
        return $this->bindings;
    }

    public function setBindings(NavigationBindings $bindings): static
    {
        $this->bindings = $bindings;

        return $this;
    }

    /**
     * Keep traversing while a direction is held, rather than once per press.
     *
     * Off by default and deliberately unpaced: a held d-pad moves focus once per
     * dispatch, so the repeat rate is the sketch's frame rate. A sketch that
     * wants something slower dispatches buttons less often.
     */
    public function repeatOnHold(bool $repeat = true): static
    {
        $this->repeat_on_hold = $repeat;

        return $this;
    }

    /**
     * The topmost interactive node under a renderer-space point, or null when
     * the point misses everything.
     */
    public function hitTest(Point $global): ?Hit
    {
        $root = $this->stage->root();

        return is_null($root) ? null : HitTest::at($root, $global);
    }

    /**
     * Deliver every active contact. Contacts are requested in pixels because
     * that is the space the tree lives in; a driver that only knows normalised
     * coordinates is converted here instead.
     */
    public function dispatchTouch(Touch $touch): bool
    {
        $handled = false;

        foreach ($touch->contacts(CoordinateSpace::PIXELS) as $contact) {
            $handled = $this->deliverTouch($contact) || $handled;
        }

        return $handled;
    }

    /**
     * Deliver the pointer's current position and whether any of its buttons are
     * down.
     *
     * Which button is deliberately not asked: pointer button labels are driver
     * specific — the SDL mouse names them by number — so the router only reports
     * pressed or not, and a node that needs more reads the device itself.
     */
    public function dispatchPointer(Pointer $pointer): bool
    {
        $global = $this->toPixels($pointer->x(), $pointer->y(), $pointer->coordinateSpace());
        $down = $pointer->anyDown();
        $held = $this->live($this->pointer_capture);

        // A press that began on a node keeps reaching it, so releasing the mouse
        // half a screen away still tells the button it is no longer held. Without
        // this the node never hears the release and stays visibly pressed.
        if (! is_null($held)) {
            if (! $down) {
                $this->pointer_capture = null;
            }

            return $held->onPointer($this->localTo($held, $global), $down);
        }

        $this->pointer_capture = null;

        $hit = $this->hitTest($global);

        if (is_null($hit)) {
            return false;
        }

        if ($pointer->anyPressed() && $hit->target->acceptsFocus()) {
            $this->focus->focus($hit->target);
        }

        if ($down) {
            $this->pointer_capture = $hit->target;
        }

        return $hit->target->onPointer($hit->local, $down);
    }

    /**
     * Route this frame's button edges. A label bound to a direction moves focus;
     * a label bound to activation reaches the focused node; anything else is
     * left alone, because a pad reports every switch on the board and most of
     * them belong to the sketch rather than to the UI.
     */
    public function dispatchButtons(ButtonPad $pad): bool
    {
        $handled = false;

        foreach ($pad->pressedLabels() as $label) {
            $handled = $this->deliverButton($label) || $handled;
        }

        if (! $this->repeat_on_hold) {
            return $handled;
        }

        foreach ($pad->holdingLabels() as $label) {
            $direction = $this->bindings->traverses($label);

            if (is_null($direction)) {
                continue;
            }

            $this->focus->move($direction);
            $handled = true;
        }

        return $handled;
    }

    /**
     * A contact focuses what it lands on, but only as it arrives — refocusing on
     * every MOVED frame would fight a drag that started somewhere else.
     */
    protected function deliverTouch(TouchContact $contact): bool
    {
        $global = $this->toPixels($contact->x, $contact->y, $contact->space);

        if ($contact->phase === TouchPhase::BEGAN) {
            return $this->beginTouch($contact, $global);
        }

        $held = $this->live($this->captures[$contact->id] ?? null);

        if ($this->ends($contact->phase)) {
            unset($this->captures[$contact->id]);
        }

        // A contact whose BEGAN we never saw — the tree changed under it, or the
        // driver started reporting mid-gesture — is still worth delivering to
        // whatever is under it now, which is the old behaviour.
        if (is_null($held)) {
            $hit = $this->hitTest($global);

            return is_null($hit) ? false : $hit->target->onTouch($contact, $hit->local);
        }

        return $held->onTouch($contact, $this->localTo($held, $global));
    }

    protected function beginTouch(TouchContact $contact, Point $global): bool
    {
        $hit = $this->hitTest($global);

        if (is_null($hit)) {
            return false;
        }

        if ($hit->target->acceptsFocus()) {
            $this->focus->focus($hit->target);
        }

        $this->capture($contact->id, $hit->target);

        return $hit->target->onTouch($contact, $hit->local);
    }

    /**
     * Bind a contact to the node it went down on for the rest of the gesture.
     *
     * This is what lets a finger slide off a button and cancel it: the button
     * hears the release wherever it happens, sees that it landed outside, and
     * unpresses without firing. Hit-testing every phase instead would hand the
     * release to whatever the finger drifted over.
     */
    protected function capture(int|string $id, InputTarget $target): void
    {
        $this->captures[$id] = $target;
    }

    protected function ends(TouchPhase $phase): bool
    {
        return ($phase === TouchPhase::ENDED) || ($phase === TouchPhase::CANCELLED);
    }

    /**
     * A captured node that has since been detached or hidden is no capture at
     * all — holding it would deliver a release into a subtree nobody can see.
     */
    protected function live(?InputTarget $target): ?InputTarget
    {
        if (is_null($target) || ! $target->isVisible()) {
            return null;
        }

        $root = $this->stage->root();
        $node = $target;

        while (! is_null($node)) {
            if ($node === $root) {
                return $target;
            }

            $node = $node->parent();
        }

        return null;
    }

    protected function localTo(InputTarget $target, Point $global): Point
    {
        $origin = $target->globalOrigin();

        return new Point($global->x - $origin->x, $global->y - $origin->y);
    }

    protected function deliverButton(string $label): bool
    {
        $direction = $this->bindings->traverses($label);

        if (! is_null($direction)) {
            $this->focus->move($direction);

            return true;
        }

        if (! $this->bindings->activates($label)) {
            return false;
        }

        return $this->focus->focused()?->onButton($label) ?? false;
    }

    /**
     * Normalised coordinates are scaled by the surface extent and clamped to the
     * last addressable pixel, so a contact reported at exactly 1.0 lands on the
     * right-hand edge rather than one column past it.
     */
    protected function toPixels(float $x, float $y, CoordinateSpace $space): Point
    {
        if ($space === CoordinateSpace::PIXELS) {
            return new Point((int) floor($x), (int) floor($y));
        }

        return new Point(
            $this->clamp((int) floor($x * $this->stage->width()), $this->stage->width()),
            $this->clamp((int) floor($y * $this->stage->height()), $this->stage->height()),
        );
    }

    protected function clamp(int $value, int $extent): int
    {
        return max(0, min($extent - 1, $value));
    }
}
