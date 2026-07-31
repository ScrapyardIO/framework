<?php

namespace DeptOfScrapyardRobotics\Tests\UX;

use Fabricate\Contracts\Actuation\HumanInput\CoordinateSpace;
use Fabricate\Contracts\Actuation\HumanInput\TouchPhase;
use Fabricate\Contracts\UX\Enums\CrossAxisAlignment;
use Fabricate\Framebuffers\Strategy\DirtyRegionsBuffer;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Point;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Input\InputRouter;
use Fabricate\UX\Layout\Row;
use Fabricate\UX\Node;
use Fabricate\UX\Stage;
use PHPUnit\Framework\TestCase;

/**
 * Where a gesture goes.
 *
 * Two properties carry the whole design: exactly one node hears about any given
 * event, and a gesture that lands on nothing is absorbed rather than handed to
 * whatever was nearest. A router that got either wrong would be worse than no
 * routing at all, because the failure is invisible until a user complains that
 * the wrong button fired.
 */
class InputRoutingTest extends TestCase
{
    protected const int SURFACE = 64;

    public function testTheTopmostNodeUnderAPointWins(): void
    {
        [$router, $root] = $this->staged();
        $under = new InteractiveNode('under', 0, 0, 40, 40);
        $over = new InteractiveNode('over', 10, 10, 20, 20);
        $root->add($under, $over);

        $this->assertSame($over, $router->hitTest(new Point(15, 15))?->target);
        $this->assertSame($under, $router->hitTest(new Point(5, 5))?->target, 'Outside the overlay, the node beneath it should take the hit.');
    }

    public function testTheHitCarriesNodeLocalCoordinates(): void
    {
        [$router, $root] = $this->staged();
        $nested = new InteractiveNode('nested', 4, 6, 10, 10);
        $parent = new EmptyNode(10, 10, 30, 30);
        $parent->add($nested);
        $root->add($parent);

        $hit = $router->hitTest(new Point(16, 18));

        $this->assertSame($nested, $hit?->target);
        $this->assertSame([2, 2], [$hit?->local->x, $hit?->local->y]);
    }

    /**
     * Criterion: a hit outside every node is absorbed, not dispatched to the
     * closest candidate.
     */
    public function testAHitOutsideEverythingIsAbsorbed(): void
    {
        [$router, $root] = $this->staged();
        $node = new InteractiveNode('only', 0, 0, 10, 10);
        $root->add($node);

        $this->assertNull($router->hitTest(new Point(30, 30)));
        $this->assertSame([], $node->pointers);
    }

    /**
     * A plain node is invisible to routing but still descended into, so a layout
     * container never blocks what it contains.
     */
    public function testALayoutContainerIsTransparentToRoutingButNeverATarget(): void
    {
        [$router, $root] = $this->staged();
        $left = new InteractiveNode('left', 0, 0, 10, 10);
        $right = new InteractiveNode('right', 0, 0, 10, 10);
        $row = new Row(cross_axis: CrossAxisAlignment::START);
        $row->add($left, $right);
        $root->add($row);
        $root->layout(Constraints::tight(new Size(self::SURFACE, self::SURFACE)));

        $this->assertSame($left, $router->hitTest(new Point(3, 3))?->target);
        $this->assertSame($right, $router->hitTest(new Point(13, 3))?->target);
    }

    public function testAHiddenNodeTakesNoInput(): void
    {
        [$router, $root] = $this->staged();
        $under = new InteractiveNode('under', 0, 0, 40, 40);
        $over = new InteractiveNode('over', 0, 0, 40, 40);
        $root->add($under, $over);
        $over->hide();

        $this->assertSame($under, $router->hitTest(new Point(5, 5))?->target);
    }

    /**
     * hitTest() narrows the area within the bounds, which is how a round gauge
     * declines the corners of its own box.
     */
    public function testANodeCanNarrowItsOwnHittableArea(): void
    {
        [$router, $root] = $this->staged();
        $under = new InteractiveNode('under', 0, 0, 40, 40);
        $round = new InteractiveNode('round', 0, 0, 20, 20);
        $round->hit_shape = static fn (Point $local): bool => ((($local->x - 10) ** 2) + (($local->y - 10) ** 2)) <= 100;
        $root->add($under, $round);

        $this->assertSame($round, $router->hitTest(new Point(10, 10))?->target);
        $this->assertSame($under, $router->hitTest(new Point(0, 0))?->target, 'A corner the round node declined should fall through.');
    }

    /**
     * Criterion: one pointer event reaches exactly one node.
     */
    public function testAPointerEventReachesExactlyOneNode(): void
    {
        [$router, $root] = $this->staged();
        $under = new InteractiveNode('under', 0, 0, 40, 40);
        $over = new InteractiveNode('over', 10, 10, 20, 20);
        $root->add($under, $over);

        $pointer = (new StubPointer)->at(15.0, 15.0);
        $pointer->press();

        $this->assertTrue($router->dispatchPointer($pointer));
        $this->assertSame([[5, 5, true]], $over->pointers);
        $this->assertSame([], $under->pointers, 'The event reached a second node.');
    }

    public function testAPointerPressFocusesWhatItLandsOn(): void
    {
        [$router, $root] = $this->staged();
        $node = new InteractiveNode('target', 0, 0, 20, 20);
        $root->add($node);

        $pointer = (new StubPointer)->at(5.0, 5.0);
        $pointer->press();
        $router->dispatchPointer($pointer);

        $this->assertSame($node, $router->focus()->focused());
        $this->assertTrue($node->isFocused());
    }

    /**
     * A pointer that only knows fractions is converted by the router, so a
     * driver reporting NORMALIZED still lands in the right place.
     */
    public function testANormalizedPointerIsScaledToTheSurface(): void
    {
        [$router, $root] = $this->staged();
        $node = new InteractiveNode('target', 32, 0, 32, 32);
        $root->add($node);

        $pointer = (new StubPointer(CoordinateSpace::NORMALIZED))->at(0.75, 0.25);
        $pointer->press();
        $router->dispatchPointer($pointer);

        $this->assertSame([[16, 16, true]], $node->pointers);
    }

    public function testAPointerMissReachesNobodyAndReportsUnhandled(): void
    {
        [$router, $root] = $this->staged();
        $node = new InteractiveNode('target', 0, 0, 10, 10);
        $root->add($node);

        $pointer = (new StubPointer)->at(50.0, 50.0);
        $pointer->press();

        $this->assertFalse($router->dispatchPointer($pointer));
        $this->assertSame([], $node->pointers);
    }

    public function testTouchContactsAreDeliveredWithLocalCoordinates(): void
    {
        [$router, $root] = $this->staged();
        $node = new InteractiveNode('target', 20, 20, 20, 20);
        $root->add($node);

        $touch = (new StubTouch(self::SURFACE, self::SURFACE))->contact(25.0, 30.0);

        $this->assertTrue($router->dispatchTouch($touch));
        $this->assertSame([[5, 10]], $node->touches);
    }

    /**
     * Criterion: normalised touch maps to the right node whatever the display
     * size, which is the whole reason the sketches can stop scaling coordinates
     * by hand.
     *
     * @param  array{0: int, 1: int}  $expected_local
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('displaySizes')]
    public function testNormalizedTouchMapsToTheRightNodeAtAnyDisplaySize(int $width, int $height, array $expected_local): void
    {
        $stage = $this->stage($width, $height);
        $root = new FilledNode(0, 0, $width, $height);
        $stage->setRoot($root);
        $router = new InputRouter($stage);

        $left = new InteractiveNode('left', 0, 0, intdiv($width, 2), $height);
        $right = new InteractiveNode('right', intdiv($width, 2), 0, intdiv($width, 2), $height);
        $root->add($left, $right);

        // A panel that only ever speaks fractions, so the router does the
        // conversion rather than the driver.
        $touch = (new StubTouch(honours_request: false))->contact(0.75, 0.5, space: CoordinateSpace::NORMALIZED);
        $router->dispatchTouch($touch);

        $this->assertSame([], $left->touches, 'The contact landed on the wrong half.');
        $this->assertSame([$expected_local], $right->touches);
    }

    /**
     * @return array<string, array{0: int, 1: int, 2: array{0: int, 1: int}}>
     */
    public static function displaySizes(): array
    {
        return [
            'ssd1306' => [128, 64, [32, 32]],
            'square oled' => [64, 64, [16, 32]],
            'sdl window' => [320, 240, [80, 120]],
        ];
    }

    public function testATouchThatBeginsOnANodeFocusesIt(): void
    {
        [$router, $root] = $this->staged();
        $node = new InteractiveNode('target', 0, 0, 20, 20);
        $root->add($node);

        $router->dispatchTouch((new StubTouch(self::SURFACE, self::SURFACE))->contact(5.0, 5.0));

        $this->assertSame($node, $router->focus()->focused());
    }

    /**
     * A gesture belongs to the node it began on. Dragging across a neighbour
     * neither focuses it nor delivers to it — the contact keeps reporting to
     * where it started, in coordinates local to that node even once it is well
     * outside the box.
     */
    public function testADragStaysWithTheNodeItBeganOn(): void
    {
        [$router, $root] = $this->staged();
        $first = new InteractiveNode('first', 0, 0, 20, 20);
        $second = new InteractiveNode('second', 30, 0, 20, 20);
        $root->add($first, $second);

        $router->dispatchTouch((new StubTouch(self::SURFACE, self::SURFACE))->contact(5.0, 5.0, TouchPhase::BEGAN));
        $router->dispatchTouch((new StubTouch(self::SURFACE, self::SURFACE))->contact(35.0, 5.0, TouchPhase::MOVED));

        $this->assertSame($first, $router->focus()->focused());
        $this->assertSame([], $second->touches, 'The drag was handed to a node it never began on.');
        $this->assertSame([[5, 5], [35, 5]], $first->touches);
    }

    /**
     * The release is the event that matters here: a control decides whether to
     * fire by looking at where the contact ended, and it can only do that if the
     * release reaches it at all.
     */
    public function testAReleaseOutsideStillReachesTheNodeTheContactBeganOn(): void
    {
        [$router, $root] = $this->staged();
        $node = new InteractiveNode('target', 0, 0, 20, 20);
        $elsewhere = new InteractiveNode('elsewhere', 30, 0, 20, 20);
        $root->add($node, $elsewhere);

        $router->dispatchTouch((new StubTouch(self::SURFACE, self::SURFACE))->contact(5.0, 5.0, TouchPhase::BEGAN));
        $router->dispatchTouch((new StubTouch(self::SURFACE, self::SURFACE))->contact(35.0, 5.0, TouchPhase::ENDED));

        $this->assertSame([[5, 5], [35, 5]], $node->touches);
        $this->assertSame([], $elsewhere->touches);
    }

    /**
     * Capture lasts exactly one gesture. The next contact hit-tests afresh, or a
     * node would own the panel forever after the first tap.
     */
    public function testCaptureIsReleasedWhenTheContactEnds(): void
    {
        [$router, $root] = $this->staged();
        $first = new InteractiveNode('first', 0, 0, 20, 20);
        $second = new InteractiveNode('second', 30, 0, 20, 20);
        $root->add($first, $second);

        $router->dispatchTouch((new StubTouch(self::SURFACE, self::SURFACE))->contact(5.0, 5.0, TouchPhase::BEGAN));
        $router->dispatchTouch((new StubTouch(self::SURFACE, self::SURFACE))->contact(5.0, 5.0, TouchPhase::ENDED));
        $router->dispatchTouch((new StubTouch(self::SURFACE, self::SURFACE))->contact(35.0, 5.0, TouchPhase::BEGAN));

        $this->assertSame([[5, 5]], $second->touches);
    }

    /**
     * A pointer released half a screen away from where it was pressed still has
     * to tell the node it pressed, or the node stays visibly held down for good.
     */
    public function testAPointerReleasedElsewhereStillReachesWhereItWasPressed(): void
    {
        [$router, $root] = $this->staged();
        $node = new InteractiveNode('target', 0, 0, 20, 20);
        $elsewhere = new InteractiveNode('elsewhere', 30, 0, 20, 20);
        $root->add($node, $elsewhere);

        $pointer = (new StubPointer)->at(5.0, 5.0);
        $pointer->press();
        $router->dispatchPointer($pointer);

        $pointer->at(35.0, 5.0)->press(false);
        $router->dispatchPointer($pointer);

        $this->assertSame([[5, 5, true], [35, 5, false]], $node->pointers);
        $this->assertSame([], $elsewhere->pointers);

        // And the capture is gone, so the next press lands where it is aimed.
        $pointer->press();
        $router->dispatchPointer($pointer);

        $this->assertSame([[5, 5, true]], $elsewhere->pointers);
    }

    /**
     * A captured node that leaves the tree mid-gesture takes its capture with
     * it, rather than swallowing everything that follows.
     */
    public function testAGestureIsReleasedWhenItsNodeLeavesTheTree(): void
    {
        [$router, $root] = $this->staged();
        $going = new InteractiveNode('going', 0, 0, 20, 20);
        $staying = new InteractiveNode('staying', 30, 0, 20, 20);
        $root->add($going, $staying);

        $router->dispatchTouch((new StubTouch(self::SURFACE, self::SURFACE))->contact(5.0, 5.0, TouchPhase::BEGAN));

        $root->remove($going);

        $router->dispatchTouch((new StubTouch(self::SURFACE, self::SURFACE))->contact(35.0, 5.0, TouchPhase::MOVED));

        $this->assertSame([[5, 5]], $going->touches, 'A detached node kept receiving the gesture.');
        $this->assertSame([[5, 5]], $staying->touches);
    }

    public function testEveryActiveContactIsDelivered(): void
    {
        [$router, $root] = $this->staged();
        $left = new InteractiveNode('left', 0, 0, 20, 20);
        $right = new InteractiveNode('right', 30, 0, 20, 20);
        $root->add($left, $right);

        $touch = (new StubTouch(self::SURFACE, self::SURFACE))
            ->contact(5.0, 5.0)
            ->contact(35.0, 5.0);

        $this->assertTrue($router->dispatchTouch($touch));
        $this->assertCount(1, $left->touches);
        $this->assertCount(1, $right->touches);
    }

    public function testARouterWithNoRootHitsNothing(): void
    {
        $router = new InputRouter($this->stage(self::SURFACE, self::SURFACE));

        $this->assertNull($router->hitTest(new Point(0, 0)));
        $this->assertSame([], $router->focus()->order());
    }

    /**
     * @return array{0: InputRouter, 1: Node}
     */
    protected function staged(): array
    {
        $stage = $this->stage(self::SURFACE, self::SURFACE);
        $root = new FilledNode(0, 0, self::SURFACE, self::SURFACE);
        $stage->setRoot($root);

        return [new InputRouter($stage), $root];
    }

    protected function stage(int $width, int $height): Stage
    {
        $buffer = new DirtyRegionsBuffer($width, $height, UxHarness::spec());

        return new Stage(UxHarness::presentation($buffer, $width, $height));
    }
}
