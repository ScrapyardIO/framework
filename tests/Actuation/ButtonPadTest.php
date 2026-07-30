<?php

namespace DeptOfScrapyardRobotics\Tests\Actuation;

use Fabricate\Actuation\HumanInput\BasicButton;
use Fabricate\Actuation\HumanInput\DigitalButtonPad;
use Fabricate\Contracts\Actuation\ActuatorException;
use Fabricate\Contracts\Actuation\HumanInput\ButtonInput;
use PHPUnit\Framework\TestCase;

class ButtonPadTest extends TestCase
{
    public function testButtonPollingTracksPressAndReleaseEdges(): void
    {
        $input = new FakeButtonInput;
        $button = new BasicButton('A', $input, hold_ms: 0);

        $button->poll();
        $this->assertFalse($button->isDown());

        $input->down = true;
        $button->poll();
        $this->assertTrue($button->isDown());
        $this->assertTrue($button->isPressed());
        $this->assertTrue($button->isHolding());

        $button->poll();
        $this->assertFalse($button->isPressed());

        $input->down = false;
        $button->poll();
        $this->assertTrue($button->wasReleased());
        $this->assertSame(0, $button->heldMs());
        $this->assertCount(4, $button->history());
    }

    public function testPadSupportsLabelsEdgesAndChords(): void
    {
        $a = new FakeButtonInput;
        $b = new FakeButtonInput;
        $polls = 0;
        $pad = new DigitalButtonPad([
            new BasicButton('A', $a),
            new BasicButton('B', $b),
        ], function () use (&$polls): void {
            $polls++;
        });

        $a->down = true;
        $pad->poll();

        $this->assertSame(1, $polls);
        $this->assertSame(['A', 'B'], $pad->labels());
        $this->assertSame(['A'], $pad->downLabels());
        $this->assertSame(['A'], $pad->pressedLabels());
        $this->assertTrue($pad->anyDown('A', 'B'));
        $this->assertFalse($pad->allDown('A', 'B'));

        $b->down = true;
        $pad->poll();
        $this->assertTrue($pad->chord('A', 'B'));
        $this->assertTrue($pad->anyPressed());
    }

    public function testPadRejectsDuplicateAndMissingLabels(): void
    {
        $this->expectException(ActuatorException::class);

        new DigitalButtonPad([
            new BasicButton('A', new FakeButtonInput),
            new BasicButton('A', new FakeButtonInput),
        ]);
    }

    public function testPadRejectsMissingLookups(): void
    {
        $pad = new DigitalButtonPad([new BasicButton('A', new FakeButtonInput)]);

        $this->expectException(ActuatorException::class);
        $pad->button('missing');
    }
}

class FakeButtonInput implements ButtonInput
{
    public bool $down = false;

    public function isDown(): bool
    {
        return $this->down;
    }

    public function close(): void {}
}
