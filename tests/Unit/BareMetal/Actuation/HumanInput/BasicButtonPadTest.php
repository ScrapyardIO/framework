<?php

use BareMetal\Actuation\HumanInput\BasicButton;
use BareMetal\Actuation\HumanInput\DigitalButtonPad;
use BareMetal\Actuation\HumanInput\Enums\ButtonHoldThreshold;
use BareMetal\Actuation\HumanInput\LatchedButton;
use BareMetal\Contracts\Actuators\ActuationException;
use BareMetal\Contracts\Actuators\HumanInput\ButtonComponent;
use BareMetal\Contracts\Actuators\HumanInput\ButtonPad;
use DeptOfScrapyardRobotics\Tests\Fixtures\BareMetal\Actuation\FakeBasicButton;

test('BasicButton implements ButtonComponent and exposes the wrapped IC', function () {
    $ic = new FakeBasicButton;
    $button = new BasicButton('A', $ic);

    expect($button)->toBeInstanceOf(ButtonComponent::class)
        ->and($button->label())->toBe('A')
        ->and($button->button())->toBe($ic);
});

test('BasicButton poll tracks down, press edge, and release edge', function () {
    $ic = new FakeBasicButton;
    $button = new BasicButton('START', $ic);

    $button->poll();
    expect($button->isDown())->toBeFalse()
        ->and($button->isPressed())->toBeFalse()
        ->and($button->wasReleased())->toBeFalse();

    $ic->press();
    $button->poll();
    expect($button->isDown())->toBeTrue()
        ->and($button->isPressed())->toBeTrue()
        ->and($button->wasReleased())->toBeFalse();

    $button->poll();
    expect($button->isDown())->toBeTrue()
        ->and($button->isPressed())->toBeFalse()
        ->and($button->wasReleased())->toBeFalse();

    $ic->release();
    $button->poll();
    expect($button->isDown())->toBeFalse()
        ->and($button->isPressed())->toBeFalse()
        ->and($button->wasReleased())->toBeTrue();
});

test('BasicButton isHolding respects the hold threshold', function () {
    $ic = new FakeBasicButton(true);
    $button = new BasicButton('HOLD', $ic, hold_ms: ButtonHoldThreshold::SHORT->value);

    $button->poll();
    expect($button->isHolding())->toBeFalse()
        ->and($button->heldMs())->toBeLessThan(ButtonHoldThreshold::SHORT->value);

    usleep(ButtonHoldThreshold::SHORT->value * 1000);
    $button->poll();

    expect($button->isHolding())->toBeTrue()
        ->and($button->heldMs())->toBeGreaterThanOrEqual(ButtonHoldThreshold::SHORT->value);
});

test('BasicButton setHoldMs accepts an enum and returns the component', function () {
    $button = new BasicButton('B', new FakeBasicButton);

    expect($button->setHoldMs(ButtonHoldThreshold::LONG))->toBe($button)
        ->and($button->holdMs())->toBe(ButtonHoldThreshold::LONG->value);
});

test('LatchedButton reports the latched down state', function () {
    $latch = new LatchedButton;

    expect($latch->isDown())->toBeFalse();

    $latch->latch(true);
    expect($latch->isDown())->toBeTrue();

    $latch->latch(false);
    expect($latch->isDown())->toBeFalse();
});

test('DigitalButtonPad indexes BasicButtons by label and supports chord helpers', function () {
    $a = new FakeBasicButton;
    $b = new FakeBasicButton;
    $pad = new DigitalButtonPad([
        new BasicButton('A', $a),
        new BasicButton('B', $b),
    ]);

    expect($pad)->toBeInstanceOf(ButtonPad::class)
        ->and($pad->labels())->toBe(['A', 'B'])
        ->and($pad->has('A'))->toBeTrue()
        ->and($pad->has('X'))->toBeFalse();

    $a->press();
    $pad->poll();

    expect($pad->isDown('A'))->toBeTrue()
        ->and($pad->isPressed('A'))->toBeTrue()
        ->and($pad->downLabels())->toBe(['A'])
        ->and($pad->pressedLabels())->toBe(['A'])
        ->and($pad->anyDown('A', 'B'))->toBeTrue()
        ->and($pad->allDown('A', 'B'))->toBeFalse()
        ->and($pad->chord('A'))->toBeTrue();

    $b->press();
    $pad->poll();

    expect($pad->chord('A', 'B'))->toBeTrue()
        ->and($pad->anyPressed())->toBeTrue();
});

test('DigitalButtonPad before_poll hook runs once per poll', function () {
    $latch = new LatchedButton;
    $calls = 0;
    $pad = new DigitalButtonPad(
        [new BasicButton('C', $latch)],
        function () use (&$calls, $latch): void {
            $calls++;
            $latch->latch(true);
        },
    );

    $pad->poll();

    expect($calls)->toBe(1)
        ->and($pad->isDown('C'))->toBeTrue()
        ->and($pad->isPressed('C'))->toBeTrue();
});

test('DigitalButtonPad rejects duplicate labels and missing lookups', function () {
    expect(fn () => new DigitalButtonPad([
        new BasicButton('A', new FakeBasicButton),
        new BasicButton('A', new FakeBasicButton),
    ]))->toThrow(ActuationException::class);

    $pad = new DigitalButtonPad([new BasicButton('A', new FakeBasicButton)]);

    expect(fn () => $pad->button('Z'))->toThrow(ActuationException::class);
});
