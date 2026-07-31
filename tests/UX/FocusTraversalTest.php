<?php

namespace DeptOfScrapyardRobotics\Tests\UX;

use Fabricate\Contracts\Actuation\HumanInput\GameControllerButton;
use Fabricate\Contracts\UX\Enums\FocusDirection;
use Fabricate\Framebuffers\Strategy\DirtyRegionsBuffer;
use Fabricate\UX\Input\InputRouter;
use Fabricate\UX\Input\NavigationBindings;
use Fabricate\UX\Layout\Column;
use Fabricate\UX\Layout\Row;
use Fabricate\UX\Node;
use Fabricate\UX\Stage;
use PHPUnit\Framework\TestCase;

/**
 * Keyboard-and-d-pad navigation.
 *
 * Traversal order is derived from the tree every time it is asked for rather
 * than registered by nodes, so the cases below are as much about what happens
 * when the tree changes underneath focus as about the happy path. A ring that
 * only worked on a static tree would break the first time a menu item was
 * hidden.
 *
 * Buttons are driven through a real DigitalButtonPad over stub switches, so the
 * press and release edges traversal depends on are the production ones.
 */
class FocusTraversalTest extends TestCase
{
    protected const int SURFACE = 64;

    /**
     * Criterion: traversal order is tree order — depth-first, children in paint
     * order — and it is stable across repeated queries.
     */
    public function testTraversalOrderFollowsTheTree(): void
    {
        [$router, $root] = $this->staged();
        $first = new InteractiveNode('first', 0, 0, 10, 10);
        $nested = new InteractiveNode('nested', 0, 0, 10, 10);
        $second = new InteractiveNode('second', 0, 12, 10, 10);
        $second->add($nested);
        $third = new InteractiveNode('third', 0, 24, 10, 10);

        $column = new Column;
        $column->add($first, $second, $third);
        $root->add($column);

        $this->assertSame(
            ['first', 'second', 'nested', 'third'],
            $this->names($router),
            'A parent comes before its children, and siblings in paint order.',
        );

        $this->assertSame($this->names($router), $this->names($router), 'The order must not drift between queries.');
    }

    /**
     * Criterion: traversal wraps at both ends.
     */
    public function testTraversalWrapsInBothDirections(): void
    {
        [$router, $nodes] = $this->threeInARow();

        $this->assertSame($nodes[0], $router->focus()->next(), 'With nothing focused, forward starts at the beginning.');
        $this->assertSame($nodes[1], $router->focus()->next());
        $this->assertSame($nodes[2], $router->focus()->next());
        $this->assertSame($nodes[0], $router->focus()->next(), 'Forward past the end must wrap.');

        $this->assertSame($nodes[2], $router->focus()->previous(), 'Backward past the start must wrap.');
    }

    public function testBackwardFromNothingStartsAtTheEnd(): void
    {
        [$router, $nodes] = $this->threeInARow();

        $this->assertSame($nodes[2], $router->focus()->move(FocusDirection::PREVIOUS));
    }

    /**
     * A node that declines focus drops out of the order without leaving a gap in
     * it, which is what makes a disabled menu item skip rather than trap.
     */
    public function testANodeThatDeclinesFocusIsSkipped(): void
    {
        [$router, $nodes] = $this->threeInARow();
        $nodes[1]->focusable(false);

        $router->focus()->focus($nodes[0]);

        $this->assertSame($nodes[2], $router->focus()->next());
        $this->assertSame(['a', 'c'], $this->names($router));
    }

    public function testAHiddenSubtreeDropsOutOfTheOrder(): void
    {
        [$router, $root] = $this->staged();
        $visible = new InteractiveNode('visible', 0, 0, 10, 10);
        $buried = new InteractiveNode('buried', 0, 0, 10, 10);
        $group = new Row;
        $group->add($buried);
        $root->add($visible, $group);

        $group->hide();

        $this->assertSame(['visible'], $this->names($router));
    }

    /**
     * Focus can be left pointing at a node that has since been hidden or
     * removed. Traversal must recover by restarting rather than getting stuck.
     */
    public function testTraversalRecoversWhenTheFocusedNodeLeavesTheOrder(): void
    {
        [$router, $nodes] = $this->threeInARow();
        $router->focus()->focus($nodes[1]);

        $nodes[1]->hide();

        $this->assertSame($nodes[0], $router->focus()->next());
    }

    /**
     * The expensive version of the same problem: a control that has gone away
     * must not still be able to fire. A menu item hidden while focused, and then
     * an action button pressed, has to reach nobody rather than the item that is
     * no longer on screen.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('departures')]
    public function testAnActionButtonNeverReachesANodeThatLeftTheOrder(string $how): void
    {
        [$router, $nodes, $stage] = $this->threeInARow();
        $router->focus()->focus($nodes[1]);

        match ($how) {
            'hidden' => $nodes[1]->hide(),
            'disabled' => $nodes[1]->focusable(false),
            'removed' => $stage->root()?->remove($nodes[1]),
        };

        $pad = new StubPad('ok');

        $this->assertFalse($router->dispatchButtons($pad->frame('ok')));
        $this->assertSame([], $nodes[1]->buttons, 'A departed node was still able to fire.');
        $this->assertNull($router->focus()->focused());
        $this->assertSame(1, $nodes[1]->focus_lost, 'The node should have been told it lost focus.');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function departures(): array
    {
        return [
            'hidden' => ['hidden'],
            'disabled' => ['disabled'],
            'removed' => ['removed'],
        ];
    }

    /**
     * The case a check on the node alone would miss: the node is still visible
     * and still willing, but the panel it lives in has been hidden.
     */
    public function testHidingAnAncestorAlsoTakesFocusAwayFromTheNodeInsideIt(): void
    {
        [$router, $root] = $this->staged();
        $buried = new InteractiveNode('buried', 0, 0, 10, 10);
        $group = new Row;
        $group->add($buried);
        $root->add($group);

        $router->focus()->focus($buried);
        $group->hide();

        $pad = new StubPad('ok');

        $this->assertTrue($buried->isVisible(), 'The node itself was never hidden.');
        $this->assertTrue($buried->acceptsFocus());
        $this->assertFalse($router->dispatchButtons($pad->frame('ok')));
        $this->assertSame([], $buried->buttons, 'A node inside a hidden panel was still able to fire.');
        $this->assertNull($router->focus()->focused());
    }

    public function testFocusNotifiesBothSidesExactlyOnce(): void
    {
        [$router, $nodes] = $this->threeInARow();

        $router->focus()->focus($nodes[0]);
        $router->focus()->focus($nodes[0]);

        $this->assertSame(1, $nodes[0]->focus_gained, 'Refocusing the focused node must be a no-op.');

        $router->focus()->focus($nodes[1]);

        $this->assertSame(1, $nodes[0]->focus_lost);
        $this->assertSame(1, $nodes[1]->focus_gained);
    }

    /**
     * Focus is node state, so gaining or losing it repaints — that is what draws
     * a focus ring without the node having to remember to invalidate.
     */
    public function testFocusChangesDamageTheNodesInvolved(): void
    {
        [$router, $nodes, $stage] = $this->threeInARow();
        $stage->render();

        $router->focus()->focus($nodes[1]);

        $this->assertTrue($stage->isDirty());
        $this->assertEquals([[10, 0, 19, 9]], $this->regions($stage));
    }

    /**
     * A bound label moves focus; an unbound one is the focused node's business.
     */
    public function testBoundLabelsTraverseAndTheRestReachTheFocusedNode(): void
    {
        [$router, $nodes] = $this->threeInARow();
        $pad = new StubPad(
            GameControllerButton::DPAD_DOWN->value,
            GameControllerButton::DPAD_UP->value,
            GameControllerButton::SOUTH->value,
        );

        $router->dispatchButtons($pad->frame(GameControllerButton::DPAD_DOWN->value));
        $this->assertSame($nodes[0], $router->focus()->focused());

        $router->dispatchButtons($pad->frame(GameControllerButton::DPAD_DOWN->value));
        $this->assertSame($nodes[0], $router->focus()->focused(), 'A held button is not a new press.');

        $router->dispatchButtons($pad->frame());
        $router->dispatchButtons($pad->frame(GameControllerButton::DPAD_DOWN->value));
        $this->assertSame($nodes[1], $router->focus()->focused());

        $router->dispatchButtons($pad->frame(GameControllerButton::SOUTH->value));
        $this->assertSame([GameControllerButton::SOUTH->value], $nodes[1]->buttons);
    }

    public function testAButtonWithNothingFocusedIsUnhandled(): void
    {
        [$router] = $this->threeInARow();
        $pad = new StubPad(GameControllerButton::SOUTH->value);

        $this->assertFalse($router->dispatchButtons($pad->frame(GameControllerButton::SOUTH->value)));
    }

    /**
     * The same navigation over a hand-wired pad whose buttons are called what
     * the wiring says, which is the point of binding labels rather than an enum.
     */
    public function testAPlainDirectionalPadNavigatesWithNoConfiguration(): void
    {
        [$router, $nodes] = $this->threeInARow();
        $pad = new StubPad('up', 'down', 'ok');

        $router->dispatchButtons($pad->frame('down'));
        $router->dispatchButtons($pad->frame());
        $router->dispatchButtons($pad->frame('down'));

        $this->assertSame($nodes[1], $router->focus()->focused());

        $router->dispatchButtons($pad->frame('ok'));

        $this->assertSame(['ok'], $nodes[1]->buttons);
    }

    /**
     * A pad reports every switch wired to it, and on a robot most of them are
     * the sketch's — a shutter release, an e-stop, a mode toggle. Only the
     * labels bound to activation are the UI's business.
     */
    public function testAnUnboundLabelIsLeftForTheSketchRatherThanFired(): void
    {
        [$router, $nodes] = $this->threeInARow();
        $router->focus()->focus($nodes[1]);
        $pad = new StubPad('shutter');

        $this->assertFalse($router->dispatchButtons($pad->frame('shutter')));
        $this->assertSame([], $nodes[1]->buttons, 'An unbound label reached the focused node.');
        $this->assertSame($nodes[1], $router->focus()->focused(), 'Focus should be undisturbed.');
    }

    public function testNarrowingTheActivationSetStopsALabelThatUsedToFire(): void
    {
        [$router, $nodes] = $this->threeInARow();
        $router->focus()->focus($nodes[1]);
        $pad = new StubPad('ok', 'fire');

        $router->setBindings($router->bindings()->withActivation(['fire']));

        $this->assertFalse($router->dispatchButtons($pad->frame('ok')));
        $this->assertSame([], $nodes[1]->buttons, "'ok' fires only because the default bindings say so.");

        $this->assertTrue($router->dispatchButtons($pad->frame('fire')));
        $this->assertSame(['fire'], $nodes[1]->buttons);
    }

    public function testBindingsCanBeReplacedWholesale(): void
    {
        [$router, $nodes] = $this->threeInARow();
        $router->setBindings(new NavigationBindings(['fwd' => FocusDirection::NEXT], ['fire']));
        $pad = new StubPad('fwd', 'fire', 'ok', GameControllerButton::DPAD_DOWN->value);

        $router->dispatchButtons($pad->frame(GameControllerButton::DPAD_DOWN->value));
        $this->assertNull($router->focus()->focused(), 'The default binding should no longer apply.');

        $router->dispatchButtons($pad->frame('fwd'));
        $this->assertSame($nodes[0], $router->focus()->focused());

        $router->dispatchButtons($pad->frame('ok'));
        $this->assertSame([], $nodes[0]->buttons, 'The default activation should no longer apply either.');

        $router->dispatchButtons($pad->frame('fire'));
        $this->assertSame(['fire'], $nodes[0]->buttons);
    }

    /**
     * Repeat-on-hold is opt-in because its rate is the caller's frame rate, and
     * a menu that scrolled at 60Hz would be unusable.
     */
    public function testHoldingADirectionOnlyRepeatsWhenAskedTo(): void
    {
        [$router, $nodes] = $this->threeInARow();
        $pad = new StubPad('down');

        $router->dispatchButtons($pad->frame('down'));
        $router->dispatchButtons($pad->frame('down'));
        $this->assertSame($nodes[0], $router->focus()->focused());

        $router->repeatOnHold();
        $router->dispatchButtons($pad->frame('down'));

        $this->assertSame($nodes[1], $router->focus()->focused());
    }

    public function testClearingFocusTellsTheNodeItLostIt(): void
    {
        [$router, $nodes] = $this->threeInARow();
        $router->focus()->focus($nodes[0]);

        $router->focus()->clear();

        $this->assertNull($router->focus()->focused());
        $this->assertFalse($nodes[0]->isFocused());
    }

    /**
     * @return array{0: InputRouter, 1: array<int, InteractiveNode>, 2: Stage}
     */
    protected function threeInARow(): array
    {
        [$router, $root, $stage] = $this->staged();

        $nodes = [
            new InteractiveNode('a', 0, 0, 10, 10),
            new InteractiveNode('b', 10, 0, 10, 10),
            new InteractiveNode('c', 20, 0, 10, 10),
        ];

        $root->add(...$nodes);

        return [$router, $nodes, $stage];
    }

    /**
     * @return array{0: InputRouter, 1: Node, 2: Stage}
     */
    protected function staged(): array
    {
        $buffer = new DirtyRegionsBuffer(self::SURFACE, self::SURFACE, UxHarness::spec());
        $stage = new Stage(UxHarness::presentation($buffer, self::SURFACE, self::SURFACE));
        $root = new FilledNode(0, 0, self::SURFACE, self::SURFACE);
        $stage->setRoot($root);

        return [new InputRouter($stage), $root, $stage];
    }

    /**
     * @return array<int, string>
     */
    protected function names(InputRouter $router): array
    {
        return array_map(
            static fn (object $node): string => $node->name,
            $router->focus()->order(),
        );
    }

    /**
     * @return array<int, array{0: int, 1: int, 2: int, 3: int}>
     */
    protected function regions(Stage $stage): array
    {
        return array_map(
            static fn (object $rect): array => $rect->toBounds(),
            $stage->damageRegions(),
        );
    }
}
