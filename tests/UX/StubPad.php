<?php

namespace DeptOfScrapyardRobotics\Tests\UX;

use Fabricate\Actuation\HumanInput\BasicButton;
use Fabricate\Actuation\HumanInput\DigitalButtonPad;

/**
 * A real DigitalButtonPad over stub switches.
 *
 * The pad and its edge detection are the production ones; only the wire is
 * fake. Hold detection is left at its default threshold so a test that wants a
 * hold has to be explicit about it.
 */
final class StubPad
{
    /**
     * @var array<string, StubButtonInput>
     */
    protected array $switches = [];

    protected DigitalButtonPad $pad;

    public function __construct(string ...$labels)
    {
        $buttons = [];

        foreach ($labels as $label) {
            $this->switches[$label] = new StubButtonInput;
            $buttons[] = new BasicButton($label, $this->switches[$label], hold_ms: 0);
        }

        $this->pad = new DigitalButtonPad($buttons);
    }

    public function pad(): DigitalButtonPad
    {
        return $this->pad;
    }

    /**
     * Hold $labels down and everything else up, then sample — one frame of
     * input.
     */
    public function frame(string ...$labels): DigitalButtonPad
    {
        foreach ($this->switches as $label => $switch) {
            $switch->down = in_array($label, $labels, true);
        }

        return $this->pad->poll();
    }
}
