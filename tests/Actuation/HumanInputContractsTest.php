<?php

namespace DeptOfScrapyardRobotics\Tests\Actuation;

use Fabricate\Contracts\Actuation\HumanInput\CoordinateSpace;
use Fabricate\Contracts\Actuation\HumanInput\GameController;
use Fabricate\Contracts\Actuation\HumanInput\GameControllerAxis;
use Fabricate\Contracts\Actuation\HumanInput\GameControllerButton;
use Fabricate\Contracts\Actuation\HumanInput\Pointer;
use Fabricate\Contracts\Actuation\HumanInput\Touch;
use Fabricate\Contracts\Actuation\HumanInput\TouchContact;
use Fabricate\Contracts\Actuation\HumanInput\TouchPhase;
use Fabricate\Contracts\Actuation\Interfaces\ButtonPad;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class HumanInputContractsTest extends TestCase
{
    public function testTouchContactCarriesTransportNeutralCoordinatesAndPhase(): void
    {
        $contact = new TouchContact(
            id: 2,
            x: 0.25,
            y: 0.75,
            phase: TouchPhase::MOVED,
            space: CoordinateSpace::NORMALIZED,
            pressure: 0.5,
        );

        $this->assertSame(2, $contact->id);
        $this->assertSame(0.25, $contact->x);
        $this->assertSame(TouchPhase::MOVED, $contact->phase);
        $this->assertSame('normalized', $contact->space->value);
    }

    public function testTouchPointerAndControllerExposeRequiredSurfaces(): void
    {
        $this->assertTrue((new ReflectionClass(Touch::class))->hasMethod('contacts'));
        $this->assertTrue(is_subclass_of(Pointer::class, ButtonPad::class));
        $this->assertTrue(is_subclass_of(GameController::class, ButtonPad::class));
        $this->assertTrue((new ReflectionClass(Pointer::class))->hasMethod('wheelY'));
        $this->assertTrue((new ReflectionClass(GameController::class))->hasMethod('axis'));
    }

    public function testControllerEnumsUseStandardizedNames(): void
    {
        $this->assertSame('south', GameControllerButton::SOUTH->value);
        $this->assertSame('left_x', GameControllerAxis::LEFT_X->value);
        $this->assertSame('pixels', CoordinateSpace::PIXELS->value);
    }
}
